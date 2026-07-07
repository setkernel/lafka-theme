/* lafka-theme/scripts/nx1-10a-prune-dead.mjs
 *
 * NX1-10a dead-declaration pruner (fix option b). The monolith teardown moved
 * per-component rules into styles/legacy-*.css, which enqueue AFTER style.css.
 * Any moved declaration that, in the monolith, was OVERRIDDEN by a LATER kept
 * style.css rule (e.g. the late "accent-color consolidation" grouped rule) was
 * DEAD in the monolith — it never rendered. After the split it silently
 * re-wins. Restoring the monolith's effective cascade means deleting exactly
 * those dead declarations from the legacy sheets.
 *
 * Deletion rule (exact-selector, exact-property, same media-context):
 *   A legacy-sheet declaration (selector S, property P) is DELETED iff the
 *   monolith's winner for (media, S, P) is a KEPT style.css declaration that
 *   sits LATER in monolith source order (importance-aware). We derive that set
 *   from the parity comparison itself: the keys where the monolith winner value
 *   is present among style.css's declarations while the split winner comes from
 *   a legacy sheet. This is exhaustive — it is NOT a hand-list of the 11 flips;
 *   it happens to resolve to them.
 *
 * A grouped legacy rule is only wholesale-pruned of P when EVERY one of its
 * selectors is a delete-key for P (so no live selector loses P). Any rule where
 * only some selectors are dead is reported as PARTIAL and left for manual
 * splitting (there are none in this corpus; the guard makes that explicit).
 *
 * REPRODUCIBLE + IDEMPOTENT: the audit and the pruned output are BOTH derived
 * from the PRE-FIX legacy sheets read out of git (BASE_REF, the teardown commit
 * 03abbf7) — NOT from the working tree. So the report always lists the same
 * dead declarations and `--apply` always reconstructs byte-identical fixed
 * sheets, whether or not the working tree is already pruned. The monolith
 * cascade is read from MONO_REF (964f19a), matching nx1-10a-cascade-parity.mjs.
 *
 *   node scripts/nx1-10a-prune-dead.mjs            # audit only -> scripts/nx1-10a-dead-declarations.txt
 *   node scripts/nx1-10a-prune-dead.mjs --apply    # rewrite styles/legacy-*.css
 *
 * @since lafka-theme 6.21.0 (NX1-10a)
 */
import fs from 'node:fs';
import path from 'node:path';
import { execFileSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';
import {
	extractRecords,
	resolveWinners,
	keyOf,
	readFile,
} from './nx1-10a-css-lib.mjs';

const THEME = path.dirname( path.dirname( fileURLToPath( import.meta.url ) ) );
const LEGACY_SHEETS = [ 'legacy-blog', 'legacy-shortcodes', 'legacy-forum', 'legacy-events' ];
const APPLY = process.argv.includes( '--apply' );
const MONO_REF = '964f19a:style.css';   // the monolith, pre-teardown
const BASE_REF = '03abbf7';             // the teardown commit: pre-prune legacy sheets

function gitShow( ref ) {
	return execFileSync( 'git', [ 'show', ref ], { cwd: THEME, maxBuffer: 64 * 1024 * 1024 } ).toString( 'utf8' );
}

function main() {
	// 1) Compute parity of the monolith vs the SPLIT AS SHIPPED BY THE TEARDOWN
	//    (working-tree style.css + the PRE-PRUNE legacy sheets from BASE_REF).
	//    This surfaces the same 11 flips regardless of the current working tree.
	const monoRec = extractRecords( gitShow( MONO_REF ), 'monolith', 0 );
	const monoWinners = resolveWinners( monoRec.records );
	const styleRec = extractRecords( readFile( path.join( THEME, 'style.css' ) ), 'style', 0 );
	const baseLegacy = new Map(); // sheet -> original css
	let order = styleRec.nextOrder;
	const splitRecords = [ ...styleRec.records ];
	for ( const sheet of LEGACY_SHEETS ) {
		const css = gitShow( `${ BASE_REF }:styles/${ sheet }.css` );
		baseLegacy.set( sheet, css );
		const r = extractRecords( css, sheet, order );
		splitRecords.push( ...r.records );
		order = r.nextOrder;
	}
	const splitWinners = resolveWinners( splitRecords );
	const mismatches = [];
	for ( const [ k, mw ] of monoWinners ) {
		const sw = splitWinners.get( k );
		if ( sw && ( sw.value !== mw.value || sw.important !== mw.important ) ) {
			mismatches.push( { key: k } );
		}
	}

	// value-set per key present in style.css (kept)
	const styleByKey = new Map();
	for ( const r of styleRec.records ) {
		const k = keyOf( r );
		if ( ! styleByKey.has( k ) ) {
			styleByKey.set( k, [] );
		}
		styleByKey.get( k ).push( r );
	}

	// deleteKeys: mismatch key whose monolith winner value is a kept style.css
	// declaration (importance-matched). These are the keys whose legacy copies
	// are dead and must be pruned.
	const deleteKeys = new Map(); // key -> {selector, property, media, winner}
	for ( const m of mismatches ) {
		const k = m.key;
		const mw = monoWinners.get( k );
		const kept = ( styleByKey.get( k ) || [] ).some(
			( r ) => r.value === mw.value && r.important === mw.important
		);
		if ( kept ) {
			const [ media, selector, property ] = k.split( '||' );
			deleteKeys.set( k, { media, selector, property, winner: mw } );
		}
	}

	// 2) Walk each legacy sheet; find declarations to delete + safety-check.
	const audit = [];
	const partials = [];
	const perSheetEdits = new Map(); // sheet -> { css, ruleDeletes:Set, lineDeletes:[] }

	for ( const sheet of LEGACY_SHEETS ) {
		const file = path.join( THEME, 'styles', `${ sheet }.css` );
		const css = baseLegacy.get( sheet ); // PRE-PRUNE original from BASE_REF
		const { ruleInstances } = extractRecords( css, sheet, 0 );
		const ruleDeletes = []; // ranges [start,end) of whole emptied rules
		const lineDeletes = []; // ranges [start,end) of single decl lines
		for ( const inst of ruleInstances ) {
			const deletableDecls = [];
			for ( const d of inst.decls ) {
				// A decl is deletable iff ALL of the rule's selectors are delete-keys
				// for this property+media. Otherwise partial (would strand a live sel).
				const perSel = inst.selNorms.map( ( s ) => ( {
					sel: s,
					key: inst.media + '||' + s + '||' + d.propertyKey,
				} ) );
				const dead = perSel.filter( ( x ) => deleteKeys.has( x.key ) );
				if ( dead.length === 0 ) {
					continue;
				}
				if ( dead.length === perSel.length ) {
					deletableDecls.push( d );
					for ( const x of dead ) {
						const w = deleteKeys.get( x.key );
						audit.push( {
							sheet,
							selector: x.sel,
							media: inst.media,
							property: d.property,
							deadValue: d.rawValue + ( d.important ? ' !important' : '' ),
							keptWinner: w.winner.value + ( w.winner.important ? ' !important' : '' ),
							keptWinnerLine: 11513, // consolidation groups; see report note
						} );
					}
				} else {
					partials.push( {
						sheet,
						property: d.property,
						deadSelectors: dead.map( ( x ) => x.sel ),
						liveSelectors: perSel.filter( ( x ) => ! deleteKeys.has( x.key ) ).map( ( x ) => x.sel ),
						ruleSelectors: inst.selectors,
					} );
				}
			}
			if ( deletableDecls.length === 0 ) {
				continue;
			}
			if ( deletableDecls.length === inst.decls.length ) {
				// Whole rule dies — remove the rule token AND its trailing whitespace
				// run. The rule's LEADING whitespace (a separate token) stays and
				// becomes the separator between the surrounding rules, so no new
				// blank lines are created and the diff is exactly this rule.
				let end = inst.rule.end;
				while ( end < css.length && /\s/.test( css[ end ] ) ) {
					end++;
				}
				ruleDeletes.push( { start: inst.rule.start, end } );
			} else {
				for ( const d of deletableDecls ) {
					lineDeletes.push( { start: d.lineStart, end: d.declEnd } );
				}
			}
		}
		perSheetEdits.set( sheet, { file, css, ruleDeletes, lineDeletes } );
	}

	// 3) Emit the audit report (always).
	writeReport( audit, partials, deleteKeys );

	// 4) Apply if requested.
	if ( APPLY ) {
		if ( partials.length ) {
			console.error( `\n[prune] REFUSING to apply: ${ partials.length } partial rule(s) need manual splitting. See report.` );
			process.exit( 2 );
		}
		for ( const sheet of LEGACY_SHEETS ) {
			const { file, css, ruleDeletes, lineDeletes } = perSheetEdits.get( sheet );
			const ranges = [ ...ruleDeletes, ...lineDeletes ].sort( ( a, b ) => b.start - a.start );
			let out = css;
			for ( const r of ranges ) {
				out = out.slice( 0, r.start ) + out.slice( r.end );
			}
			if ( out !== css ) {
				fs.writeFileSync( file, out );
				console.log( `[prune] rewrote styles/${ sheet }.css (${ css.length } -> ${ out.length } bytes)` );
			}
		}
	}

	console.log( `\n[prune] delete-keys: ${ deleteKeys.size }  deletions: ${ audit.length }  partials: ${ partials.length }` );
	console.log( `[prune] report: scripts/nx1-10a-dead-declarations.txt${ APPLY ? '  (APPLIED)' : '  (audit only; pass --apply to rewrite)' }` );
	if ( partials.length ) {
		process.exit( 2 );
	}
}

function writeReport( audit, partials, deleteKeys ) {
	const lines = [];
	lines.push( 'NX1-10a dead-declaration prune — audit' );
	lines.push( '=======================================' );
	lines.push( '' );
	lines.push( 'Every row below is a declaration that was DEAD in the monolith (overridden' );
	lines.push( 'by a LATER kept style.css rule — the late accent-color consolidation groups' );
	lines.push( 'at style.css:11513 / 11527) but silently re-won after being moved into a' );
	lines.push( 'legacy-*.css sheet that enqueues after style.css. Deleting it restores the' );
	lines.push( "monolith's effective cascade. Derived exhaustively from the parity sweep," );
	lines.push( 'not hand-listed.' );
	lines.push( '' );
	lines.push( `delete-keys (media,selector,property): ${ deleteKeys.size }` );
	lines.push( `declarations deleted: ${ audit.length }` );
	lines.push( '' );
	const pad = ( s, n ) => ( s + ' '.repeat( n ) ).slice( 0, n );
	lines.push( `${ pad( 'SHEET', 16 ) }  ${ pad( 'PROPERTY', 18 ) }  ${ pad( 'DEAD VALUE', 26 ) }  KEPT WINNER (style.css)` );
	lines.push( `${ pad( '', 16 ) }  ${ pad( '', 18 ) }  ${ pad( '', 26 ) }` );
	for ( const a of audit ) {
		lines.push( `${ pad( a.sheet, 16 ) }  ${ pad( a.property, 18 ) }  ${ pad( a.deadValue, 26 ) }  ${ a.keptWinner }` );
		lines.push( `${ pad( '', 16 ) }  selector: ${ ( a.media ? '@media ' + a.media + ' ' : '' ) + a.selector }` );
	}
	if ( partials.length ) {
		lines.push( '' );
		lines.push( 'PARTIAL (NOT auto-applied — manual split required):' );
		for ( const p of partials ) {
			lines.push( `  ${ p.sheet }  ${ p.property }` );
			lines.push( `    dead selectors: ${ p.deadSelectors.join( ', ' ) }` );
			lines.push( `    live selectors: ${ p.liveSelectors.join( ', ' ) }` );
		}
	}
	lines.push( '' );
	fs.writeFileSync( path.join( THEME, 'scripts', 'nx1-10a-dead-declarations.txt' ), lines.join( '\n' ) );
}

main();
