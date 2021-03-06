<?php
/**
 * Revisions administration panel.
 *
 * @package WordPress
 * @subpackage Administration
 */

/** WordPress Administration Bootstrap */
require_once('./admin.php');
wp_reset_vars( array( 'revision', 'action' ) );

$revision_id = absint( $revision );
$redirect = 'edit.php';

switch ( $action ) :
case 'restore' :
	if ( ! $revision = wp_get_post_revision( $revision_id ) )
		break;

	if ( ! current_user_can( 'edit_post', $revision->post_parent ) )
		break;


	if ( ! $post = get_post( $revision->post_parent ) )
		break;

	// Revisions disabled (previously checked autosavegs && ! wp_is_post_autosave( $revision ))
	if ( ( ! WP_POST_REVISIONS || ! post_type_supports( $post->post_type, 'revisions' ) ) ) {
		$redirect = 'edit.php?post_type=' . $post->post_type;
		break;
	}

	check_admin_referer( "restore-post_{$revision->ID}" );

	wp_restore_post_revision( $revision->ID );
	$redirect = add_query_arg( array( 'message' => 5, 'revision' => $revision->ID ), get_edit_post_link( $post->ID, 'url' ) );
	break;
case 'view' :
case 'edit' :
default :
	if ( ! $revision = wp_get_post_revision( $revision_id ) )
		break;
	if ( ! $post = get_post( $revision->post_parent ) )
		break;

	if ( ! current_user_can( 'read_post', $revision->ID ) || ! current_user_can( 'read_post', $post->ID ) )
		break;

	// Revisions disabled and we're not looking at an autosave
	if ( ! wp_revisions_enabled( $post ) && ! wp_is_post_autosave( $revision ) ) {
		$redirect = 'edit.php?post_type=' . $post->post_type;
		break;
	}

	$post_title = '<a href="' . get_edit_post_link() . '">' . get_the_title() . '</a>';
	$h2 = sprintf( __( 'Compare Revisions of &#8220;%1$s&#8221;' ), $post_title );
	$title = __( 'Revisions' );

	$redirect = false;
	break;
endswitch;

// Empty post_type means either malformed object found, or no valid parent was found.
if ( ! $redirect && empty( $post->post_type ) )
	$redirect = 'edit.php';

if ( ! empty( $redirect ) ) {
	wp_redirect( $redirect );
	exit;
}

// This is so that the correct "Edit" menu item is selected.
if ( ! empty( $post->post_type ) && 'post' != $post->post_type )
	$parent_file = $submenu_file = 'edit.php?post_type=' . $post->post_type;
else
	$parent_file = $submenu_file = 'edit.php';

wp_enqueue_script( 'revisions' );

$strings = array(
	'diffFromTitle' => _x( 'From: %s', 'revision from title'  ),
	'diffToTitle'   => _x( 'To: %s', 'revision to title' )
);

$settings = array(
	'post_id'     => $post->ID,
	'nonce'       => wp_create_nonce( 'revisions-ajax-nonce' ),
	'revision_id' => $revision_id
);

$strings['settings'] = $settings;
wp_localize_script( 'revisions', 'wpRevisionsL10n', $strings );

require_once( './admin-header.php' );

?>

<div class="wrap">
	<?php screen_icon(); ?>
	<div id="revision-diff-container" class="current-version right-model-loading">
		<h2 class="long-header"><?php echo $h2; ?></h2>

		<div id="loading-status" class="updated message">
			<p><span class="spinner" ></span> <?php _e( 'Calculating revision diffs' ); ?></p>
		</div>

		<div class="diff-slider-ticks-wrapper">
			<div id="diff-slider-ticks"></div>
		</div>

		<div id="revision-interact"></div>

		<div id="revisions-diff"></div>
	</div>
</div>

<script id="tmpl-revisions-diff" type="text/html">
	<div id="toggle-revision-compare-mode">
		<label>
			<input type="checkbox" id="compare-two-revisions" />
			<?php esc_attr_e( 'Compare two revisions' ); ?>
		</label>
	</div>

	<div id="diff-header">
		<div id="diff-header-from" class="diff-header">
			<div id="diff-title-from-current-version" class="diff-title">
				<?php printf( '<strong>%1$s</strong> %2$s.' , __( 'From:' ), __( 'the current version' ) ); ?>
			</div>

			<div id="diff-title-from" class="diff-title">
				<strong><?php _e( 'From:' ); ?></strong> {{{ data.titleFrom }}}
			</div>
		</div>

		<div id="diff-header-to" class="diff-header">
			<div id="diff-title-to" class="diff-title">
				<strong><?php _e( 'To:' ); ?></strong> {{{ data.titleTo }}}
			</div>

			<input type="button" id="restore-revision" class="button button-primary" data-restore-link="{{{ data.restoreLink }}}" value="<?php esc_attr_e( 'Restore This Revision' )?>" />
		</div>
	</div>

	</div>

	<div id="diff-table">{{{ data.diff }}}</div>
</script>

<script id="tmpl-revision-interact" type="text/html">
	<div id="diff-previous-revision">
		<input class="button" type="button" id="previous" value="<?php esc_attr_e( 'Previous' ); ?>" />
	</div>

	<div id="diff-next-revision">
		<input class="button" type="button" id="next" value="<?php esc_attr_e( 'Next' ); ?>" />
	</div>

	<div id="diff-slider" class="wp-slider"></div>
</script>

<script id="tmpl-revision-ticks" type="text/html">
	<div class="revision-tick completed-{{{ data.completed }}} scope-of-changes-{{{ data.scopeOfChanges }}}"></div>
</script>
<?php
require_once( './admin-footer.php' );
