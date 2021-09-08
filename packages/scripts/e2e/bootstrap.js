/**
 * Tests Bootstrap.
 */

import { existsSync } from 'fs';

// Load dotenv files.
const envFiles = [ '.llmsenv', '.llmsenv.dist' ];
envFiles.some( file => {
	const path = `${ process.cwd() }/${ file }`;
	if ( existsSync( file ) ) {
		require( 'dotenv' ).config( { path } );
	}
} );

// Setup the WP Base URL for e2e Tests.
if ( ! process.env.WORDPRESS_PORT ) {
	process.env.WORDPRESS_PORT = '8080';
}

// Allow easy override of the default base URL, for example if we want to point to a live URL.
if ( ! process.env.WP_BASE_URL ) {
	process.env.WP_BASE_URL = `http://localhost:${ process.env.WORDPRESS_PORT }`;
}

// The Jest timeout is increased because these tests are a bit slow.
jest.setTimeout( process.env.PUPPETEER_TIMEOUT || 100000 );
