<?php
/**
 * View for the Purchase Orders' email footer (default template)
 *
 * @since 0.9.11
 *
 * @var string $footer
 */

defined( 'ABSPATH' ) || die;

?>
<div class="u-row-container" style="padding: 0px;background-color: transparent">
	<div class="u-row" style="Margin: 0 auto;min-width: 320px;max-width: 550px;overflow-wrap: break-word;word-wrap: break-word;word-break: break-word;background-color: transparent;">
		<div style="border-collapse: collapse;display: table;width: 100%;background-color: transparent;">
			<!--[if (mso)|(IE)]><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="padding: 0px;background-color: transparent;" align="center"><table cellpadding="0" cellspacing="0" border="0" style="width:550px;"><tr style="background-color: transparent;"><![endif]-->

			<!--[if (mso)|(IE)]><td align="center" width="550" style="width: 550px;padding: 0px;border-top: 0px solid transparent;border-left: 0px solid transparent;border-right: 0px solid transparent;border-bottom: 0px solid transparent;" valign="top"><![endif]-->
			<div class="u-col u-col-100" style="max-width: 320px;min-width: 550px;display: table-cell;vertical-align: top;">
				<div style="width: 100% !important;">
					<!--[if (!mso)&(!IE)]><!--><div style="padding: 0px;border-top: 0px solid transparent;border-left: 0px solid transparent;border-right: 0px solid transparent;border-bottom: 0px solid transparent;"><!--<![endif]-->

						<table style="font-family:arial,helvetica,sans-serif;" role="presentation" cellpadding="0" cellspacing="0" width="100%" border="0">
							<tbody>
							<tr>
								<td style="overflow-wrap:break-word;word-break:break-word;padding:10px;font-family:arial,helvetica,sans-serif;" align="left">

									<div style="color: #888888; line-height: 140%; text-align: center; word-wrap: break-word;">
										<p style="font-size: 14px; line-height: 140%;"><?php echo wp_kses_post( html_entity_decode( $footer, ENT_COMPAT, 'UTF-8' ) ); ?></p>
									</div>

								</td>
							</tr>
							</tbody>
						</table>

						<!--[if (!mso)&(!IE)]><!--></div><!--<![endif]-->
				</div>
			</div>
			<!--[if (mso)|(IE)]></td><![endif]-->
			<!--[if (mso)|(IE)]></tr></table></td></tr></table><![endif]-->
		</div>
	</div>
</div>
