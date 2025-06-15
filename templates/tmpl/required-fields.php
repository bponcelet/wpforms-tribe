<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<# _.each( data.provider.fields, function( label, name, fields ) { #>
<tr class="wpforms-builder-provider-connection-fields-table-row">
	<td>
		<strong class="wpforms-builder-provider-connection-field-name">{{ label }}<span class="required">*</span></strong>
		<input type="hidden" name="providers[{{ data.provider.slug }}][{{ data.connection.id }}][fields_required][{{ name }}][name]" value="{{ name }}">
	</td>
	<td>
		<select class="wpforms-builder-provider-connection-field-value wpforms-required"
			name="providers[{{ data.provider.slug }}][{{ data.connection.id }}][fields_required][{{ name }}][field_id]">
			<option value=""><?php esc_html_e( '--- Select Form Field ---', 'wpforms-tribe' ); ?></option>

			<# _.each( data.fields, function( field, key ) { #>
				<option value="{{ field.id }}"<# if ( -1 !== _.findIndex( data.connection.fields_required, { 'name': name, 'field_id': field.id } ) ) { #> selected<# } #>>
					{{ field.label }}
				</option>
			<# } ); #>
		</select>
	</td>
	<td></td>
	<td></td>
</tr>
<# } ); #>
