/* lafka-theme/scripts/nx1-10a-css-lib.mjs
 *
 * Shared CSS parsing for the NX1-10a cascade-parity tooling. Extends the
 * rule/at-rule tokeniser from scripts/nx1-10a-extract.mjs with declaration-level
 * parsing and a small cascade resolver, so both the prune tool
 * (nx1-10a-prune-dead.mjs) and the parity verifier (nx1-10a-cascade-parity.mjs)
 * share one implementation.
 *
 * Cascade model (deliberately narrow, matching the defect's shape):
 *   Everything compared here lives inside `@layer legacy`. Within a single
 *   layer, for the SAME exact selector S declaring the SAME property P (so
 *   identical specificity), the winner is decided purely by (1) importance then
 *   (2) source order. That is the ONLY axis the monolith teardown could invert:
 *   a per-component rule that sat BEFORE a later grouped "accent consolidation"
 *   rule in the monolith was overridden by it; once the per-component rule is
 *   moved into a legacy-*.css sheet that loads AFTER style.css, it re-wins. We
 *   therefore group declarations by (media-context, exact-selector, property)
 *   and resolve winners by (important, order). Different selectors / specificity
 *   interactions are out of scope by construction — see the task write-up.
 *
 * @since lafka-theme 6.21.0 (NX1-10a)
 */
import fs from 'node:fs';

/**
 * Return the text inside the outer `@layer legacy { … }` block, or '' if none.
 * Offsets in the returned records are relative to the ORIGINAL css string, so
 * callers can splice the raw file.
 */
export function layerBodyRange( css ) {
	const openTok = '@layer legacy {';
	const openIdx = css.indexOf( openTok );
	if ( openIdx === -1 ) {
		return null;
	}
	const bodyStart = openIdx + openTok.length;
	const lastClose = css.lastIndexOf( '}' );
	return { bodyStart, bodyEnd: lastClose };
}

/**
 * Tokenise a CSS body into top-level items with ABSOLUTE offsets into the parent
 * string. `base` is the offset of `body[0]` within that parent. Mirrors the
 * tokeniser in nx1-10a-extract.mjs (string-aware brace matching) but records
 * offsets and does not merge whitespace.
 *
 * @return {Array<{type:string,text:string,selectors:string[],start:number,end:number,contentStart:number}>}
 */
export function tokenize( body, base = 0 ) {
	const items = [];
	let i = 0;
	const n = body.length;

	while ( i < n ) {
		if ( /\s/.test( body[ i ] ) ) {
			let j = i;
			while ( j < n && /\s/.test( body[ j ] ) ) {
				j++;
			}
			items.push( { type: 'ws', text: body.slice( i, j ), selectors: [], start: base + i, end: base + j, contentStart: base + i } );
			i = j;
			continue;
		}
		if ( body[ i ] === '/' && body[ i + 1 ] === '*' ) {
			const end = body.indexOf( '*/', i + 2 );
			const stop = end === -1 ? n : end + 2;
			items.push( { type: 'comment', text: body.slice( i, stop ), selectors: [], start: base + i, end: base + stop, contentStart: base + i } );
			i = stop;
			continue;
		}
		let j = i;
		let str = null;
		while ( j < n ) {
			const c = body[ j ];
			if ( str ) {
				if ( c === '\\' ) {
					j += 2;
					continue;
				}
				if ( c === str ) {
					str = null;
				}
				j++;
				continue;
			}
			if ( c === '"' || c === "'" ) {
				str = c;
				j++;
				continue;
			}
			if ( c === '{' || c === ';' ) {
				break;
			}
			j++;
		}
		if ( j >= n ) {
			items.push( { type: 'other', text: body.slice( i ), selectors: [], start: base + i, end: base + n, contentStart: base + i } );
			break;
		}
		if ( body[ j ] === ';' ) {
			items.push( { type: 'stmt', text: body.slice( i, j + 1 ), selectors: [], start: base + i, end: base + j + 1, contentStart: base + i } );
			i = j + 1;
			continue;
		}
		const prelude = body.slice( i, j );
		let depth = 0;
		let k = j;
		str = null;
		for ( ; k < n; k++ ) {
			const c = body[ k ];
			if ( str ) {
				if ( c === '\\' ) {
					k++;
					continue;
				}
				if ( c === str ) {
					str = null;
				}
				continue;
			}
			if ( c === '"' || c === "'" ) {
				str = c;
				continue;
			}
			if ( c === '{' ) {
				depth++;
			} else if ( c === '}' ) {
				depth--;
				if ( depth === 0 ) {
					k++;
					break;
				}
			}
		}
		const text = body.slice( i, k );
		const isAt = prelude.trim().startsWith( '@' );
		const contentStart = base + i + ( j - i ); // position of the '{'
		if ( isAt ) {
			items.push( { type: 'atblock', text, selectors: [], start: base + i, end: base + k, contentStart, prelude } );
		} else {
			const selectors = prelude.split( ',' ).map( ( s ) => s.trim() ).filter( Boolean );
			items.push( { type: 'rule', text, selectors, start: base + i, end: base + k, contentStart } );
		}
		i = k;
	}
	return items;
}

/**
 * Parse the declarations inside a style-rule token. Returns records with
 * ABSOLUTE offsets (`declStart`/`declEnd` cover "prop: value" through the
 * terminating ';', and `lineStart` extends back over leading indentation so a
 * whole physical line can be removed cleanly).
 *
 * @param {string} css  the full source string (for offset context)
 * @param {object} rule a 'rule' token from tokenize()
 */
export function parseDeclarations( css, rule ) {
	const open = css.indexOf( '{', rule.start );
	const close = rule.end - 1; // rule.end is just past the closing '}'
	const inner = css.slice( open + 1, close );
	const decls = [];
	let i = 0;
	const n = inner.length;
	let str = null;
	let paren = 0;
	let segStart = 0;
	const flush = ( segEnd ) => {
		const absStart = open + 1 + segStart;
		const absEnd = open + 1 + segEnd; // exclusive; points at ';' or close
		const raw = css.slice( absStart, absEnd );
		const colon = raw.indexOf( ':' );
		if ( colon !== -1 && raw.trim() ) {
			const property = raw.slice( 0, colon ).trim();
			let value = raw.slice( colon + 1 ).trim();
			const important = /!\s*important\s*$/i.test( value );
			value = value.replace( /!\s*important\s*$/i, '' ).trim();
			// Extend line start back over leading indentation/newline.
			let lineStart = absStart;
			while ( lineStart > open + 1 && ( css[ lineStart - 1 ] === ' ' || css[ lineStart - 1 ] === '\t' ) ) {
				lineStart--;
			}
			if ( lineStart > open + 1 && css[ lineStart - 1 ] === '\n' ) {
				lineStart--;
			}
			// The declaration end: include the ';' if present.
			let declEnd = absEnd;
			if ( css[ declEnd ] === ';' ) {
				declEnd++;
			}
			decls.push( {
				property,
				propertyKey: property.toLowerCase(),
				value: normValue( value ),
				rawValue: value,
				important,
				declStart: absStart,
				declEnd,
				lineStart,
			} );
		}
	};
	for ( i = 0; i < n; i++ ) {
		const c = inner[ i ];
		if ( str ) {
			if ( c === '\\' ) {
				i++;
				continue;
			}
			if ( c === str ) {
				str = null;
			}
			continue;
		}
		if ( c === '"' || c === "'" ) {
			str = c;
			continue;
		}
		if ( c === '(' ) {
			paren++;
			continue;
		}
		if ( c === ')' ) {
			paren--;
			continue;
		}
		if ( c === ';' && paren === 0 ) {
			flush( i );
			segStart = i + 1;
		}
	}
	// Trailing declaration without a semicolon.
	if ( inner.slice( segStart ).trim() ) {
		flush( n );
	}
	return decls;
}

/** Collapse whitespace and canonicalise combinators so equivalent selectors match. */
export function normSelector( sel ) {
	return sel
		.replace( /\s*([>+~])\s*/g, ' $1 ' )
		.replace( /\s+/g, ' ' )
		.trim();
}

/** Normalise a value for winner comparison: collapse whitespace, lowercase. */
export function normValue( val ) {
	return val.replace( /\s+/g, ' ' ).trim().toLowerCase();
}

/**
 * Flatten a stylesheet's `@layer legacy` body into declaration records keyed by
 * (media, selector, property). `order0` seeds a monotonically-increasing source
 * order; returns the next free order so callers can chain multiple sheets in
 * enqueue order to model the split cascade.
 *
 * @return {{records:Array, ruleInstances:Array, nextOrder:number}}
 */
export function extractRecords( css, source, order0 = 0 ) {
	const range = layerBodyRange( css );
	const records = [];
	const ruleInstances = [];
	let order = order0;
	if ( ! range ) {
		return { records, ruleInstances, nextOrder: order };
	}
	const body = css.slice( range.bodyStart, range.bodyEnd );
	const top = tokenize( body, range.bodyStart );
	const handleRule = ( rule, media ) => {
		const decls = parseDeclarations( css, rule );
		const selNorms = rule.selectors.map( normSelector );
		ruleInstances.push( { source, media, selectors: rule.selectors, selNorms, decls, order, rule } );
		for ( const sel of selNorms ) {
			for ( const d of decls ) {
				records.push( {
					media,
					selector: sel,
					property: d.propertyKey,
					value: d.value,
					important: d.important,
					order,
					source,
					decl: d,
				} );
			}
		}
		order++;
	};
	for ( const it of top ) {
		if ( it.type === 'rule' ) {
			handleRule( it, '' );
		} else if ( it.type === 'atblock' ) {
			const media = normValue( it.prelude );
			// Parse inner rules with correct absolute offsets.
			const innerOpen = css.indexOf( '{', it.start );
			const innerText = css.slice( innerOpen + 1, it.end - 1 );
			const innerItems = tokenize( innerText, innerOpen + 1 );
			for ( const rit of innerItems ) {
				if ( rit.type === 'rule' ) {
					handleRule( rit, media );
				} else if ( rit.type === 'atblock' ) {
					order++; // nested at-rule: count but don't descend (none in this corpus)
				}
			}
		}
	}
	return { records, ruleInstances, nextOrder: order };
}

/** key helper */
export function keyOf( r ) {
	return r.media + '||' + r.selector + '||' + r.property;
}

/**
 * Resolve the winning record for each (media,selector,property) key among the
 * supplied records. Winner = highest importance, then highest order.
 */
export function resolveWinners( records ) {
	const byKey = new Map();
	for ( const r of records ) {
		const k = keyOf( r );
		const cur = byKey.get( k );
		if ( ! cur ) {
			byKey.set( k, r );
			continue;
		}
		const better =
			( r.important && ! cur.important ) ||
			( r.important === cur.important && r.order >= cur.order );
		if ( better ) {
			byKey.set( k, r );
		}
	}
	return byKey;
}

export function readFile( p ) {
	return fs.readFileSync( p, 'utf8' );
}
