<?php
$staticImageUrl = UkrSolution\ProductLabelsPrinting\Helpers\Variables::$A4B_PLUGIN_BASE_URL . "assets/img/amazon-200x113.jpg";

$adaptiveTemplate = '<div data-adaptive="adaptive" style="display:flex;align-items:center;height:100%;width:100%;text-align:center;">
  <div data-barcode="barcode" style="display: none;margin: 2px 0;">
      <img style="width: 100%;position: absolute;bottom: 0;left: 0;" src="[barcode_img_url]" class="barcode-basic-image"/>
  </div>
  <div data-lines="lines">
      <div style="max-height: 17.6px; overflow: hidden; font-size: 16px;" class="barcode-basic-line1" >
          [line1]
      </div>
      <div style="font-size: 16px; max-height: 17.6px;" class="barcode-basic-line2">
          [line2]
      </div>
      <div style="height:33%;overflow:hidden;margin: 2px 0;position: relative;">
          <img style="width: 100%;position: absolute;bottom: 0;left: 0;" src="[barcode_img_url]" class="barcode-basic-image" />
      </div>
      <div style="font-size: 16px; max-height: 17.6px;overflow: hidden;" class="barcode-basic-line3">
          [line3]
      </div>
      <div style="font-size: 16px; max-height: 17px;overflow: hidden;" class="barcode-basic-line4">
          [line4]
      </div>
  </div>
</div>';

return array(
  array(
    'name' => 'Basic template',
    'slug' => 'default-1',
    'template' => $adaptiveTemplate,
    'is_default' => 1,
    'is_base' => 1,
    'height' => null,
    'width' => null,
    'uol_id' => 1,
    'fontStatus' => 1,
    'fontTagLink' => '<link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">',
    'fontCssRules' => "font-family: 'Roboto', sans-serif;",
  ),

  array(
    'name' => 'Example 1 - Barcode + Logo',
    'slug' => 'default-2',
    'template' => '<table style="width:100%; height:100%" cellspacing="0" cellpadding="0" >
        <tr>
          <td style="border-right: 1px solid #e2e2e2; font-size: 16px; vertical-align: middle;" align="center">
              <div style="width:40mm; margin-right:1mm; line-height:18px; max-height:14.4mm; max-height:14mm; overflow:hidden;">
                  [line1] - [line2]
                </div>
                <div style="font-size:12px; margin-top:1mm;">[line3]</div>
            </td>
            <td style="vertical-align: bottom;" align="center">
              <div style="width:24mm; overflow:hidden;">
                <img style="width:18mm; margin-left:2mm" src="' . $staticImageUrl . '"/>
                    <div style="font-size:12px; height:6mm; vertical-align:top" align="center"> Amazon Inc.</div>

              </div>
            </td>
        </tr>
        <tr>
            <td colspan="2" style="vertical-align: top;">
              <div style="height:12mm; overflow:hidden; margin-top:2mm">
                <img style="width: 100%;" src="[barcode_img_url]"/>
              </div>
            </td>
        </tr>
        </table>',
    'is_default' => 1,
    'is_base' => 0,
    'height' => 37,
    'width' => 70,
    'uol_id' => 1,
    'fontStatus' => 1,
    'fontTagLink' => '<link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">',
    'fontCssRules' => "font-family: 'Roboto', sans-serif;",
    'base_padding_uol' => 2.5,
    'barcode_type' => 'C128',
  ),

  array(
    'name' => 'Example 2 - QRCode',
    'slug' => 'default-3',
    'template' => '<table style="width:100%; height:100%;" cellspacing="0" cellpadding="0">
        <tr>
          <td>
            <div style="">
              <img style="height: 40mm; max-width:40mm; margin-right:4mm;" src="[barcode_img_url]"/>
            </div>
          </td>
          <td style="font-size: 20px; height:40mm; width: 53mm; overflow:hidden; text-align:center; vertical-align:middle">
            <div style="margin-bottom:2mm; max-height:16mm; overflow:hidden;"><b>[line1]</b></div>
            <div style="margin-bottom:1mm; ">[line2]</div>
            <div style="margin-bottom:1mm; ">[line3]</div>
            <div style="font-size:16px; max-height:8.7mm; overflow:hidden;">[line4]</div>
          </td>
        </tr>
      </table>',
    'is_default' => 1,
    'is_base' => 0,
    'height' => 48,
    'width' => 105,
    'uol_id' => 1,
    'fontStatus' => 1,
    'fontTagLink' => '<link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">',
    'fontCssRules' => "font-family: 'Roboto', sans-serif;",
    'base_padding_uol' => 4,
    'barcode_type' => 'QRCODE',
  ),

  array(
    'name' => 'Example 3 - QRCode + Product Image',
    'slug' => 'default-4',
    'template' => '<table style="width:100%; height:100%; font-size:18px;" cellspacing="0" cellpadding="0">
        <tr>
          <td style="height:20mm;" align="center">
            <img src="[product_image_url]" style="max-width:20mm; max-height:20mm;"/>
          </td>
          <td align="center">
            <div style="height: 10mm; width: 54mm; overflow:hidden; margin:0 1mm;">[line1]</div>
            <div style="margin-bottom:0mm">[line2]</div>
            <div style="margin-bottom:0mm; ">[line3]</div>
            <div style="max-height:9mm; width: 54mm;overflow:hidden; font-size:16px; margin:0 1mm;">[line4]</div>
          </td>
          <td align="center">
            <img style="width: 24mm;" src="[barcode_img_url]"/>
          </td>
        </tr>
      </table>',
    'is_default' => 1,
    'is_base' => 0,
    'height' => 30,
    'width' => 105,
    'uol_id' => 1,
    'fontStatus' => 1,
    'fontTagLink' => '<link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">',
    'fontCssRules' => "font-family: 'Roboto', sans-serif;",
    'base_padding_uol' => 2,
    'barcode_type' => 'QRCODE',
  ),

  array(
    'name' => 'Example 4 - Vertical Barcode',
    'slug' => 'default-5',
    'template' => '<div align="center">
 
    <!-- RIGHT SIDE -->
    <div style="width: 35.5mm; text-align: left; float:left; margin-right: 1.5mm;">
      <div style="font-size: 14px; max-height:15mm; overflow:hidden;">
        Apple iPhone 11 Pro 
      </div>
      <div style="font-size:11px; margin-top:2mm;">
        <img src="[product_image_url]" style="width:17mm; float:left"/>
        <div style="line-height:14px; font-size:12px; float:left; margin-left:1mm;">
          512GB<br/>
          Black<br/>
          Apple Co.<br/>
          <div style="font-size:14px; margin-top:4px;">[line2]</div>
        </div>
        <div style="clear:both;"></div>
      </div>
      
      <div style="font-size: 11px; max-height:5mm; overflow:hidden; margin:1mm 0;text-align: center;">[line4]</div>
    </div>
    
    <!-- LEFT SIDE -->
    <div style="height: 25.6mm; width:11mm; overflow: hidden; float:left;">
      <!-- ROTATED BARCODE -->
      <div style="transform: rotate(270deg); transform-origin: 0% 0%; position:relative; width: 25.6mm; top: 100%;">
        <div style="height:8mm; overflow:hidden;">
          <img style="width: 100%; margin-top:-31px;" src="[barcode_img_url]"/>
        </div>
        <div style="font-size:12px; height:2.5mm; margin-top:0.5mm; overflow:hidden;">[line3]</div>
      </div>
    </div>
   
    
    <div style="clear:both;"></div>
  </div>',
    'is_default' => 1,
    'is_base' => 0,
    'height' => 29.6,
    'width' => 52.5,
    'uol_id' => 1,
    'fontStatus' => 1,
    'fontTagLink' => '<link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">',
    'fontCssRules' => "font-family: 'Roboto', sans-serif;",
    'base_padding_uol' => 2,
    'barcode_type' => 'C128',
  ),

  array(
    'name' => 'Example 5 - Custom Fields & Attributes',
    'slug' => 'default-6',
    'template' => '<table style="width:100%; height:100%; font-size:16px;" cellspacing="0" cellpadding="0">
        <tr>
          <td style="line-height: 20px;">
            <b>Woo Id</b>: [field=ID]<br/>
            <b>SKU</b>: [cf=_sku]<br/>
            <b>Regular price</b>: <strike>[cf=_regular_price]</strike><br/>
          </td>
          <td style="line-height: 20px;">
            <b>Sale price</b>: [cf=_sale_price]
            <b>Size</b>: [attr=Size]<br/>
            <b>Color</b>: [attr=Color]<br/>
            <b>Category</b>: [category]
          </td>
        </tr>
        <tr>
          <td colspan="2">
            <div style="text-align:center; height:17mm; overflow:hidden; margin-top:3mm">
              <img src="[barcode_img_url]" style="width:100%;"/>
            </div>
          </td>
        </tr>
      </table>',
    'is_default' => 1,
    'is_base' => 0,
    'height' => 48,
    'width' => 105,
    'uol_id' => 1,
    'fontStatus' => 1,
    'fontTagLink' => '<link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">',
    'fontCssRules' => "font-family: 'Roboto', sans-serif;",
    'base_padding_uol' => 4,
    'barcode_type' => 'C128',
  ),

  array(
    'name' => 'Example 1 - Barcode + Logo',
    'slug' => 'default-7',
    'template' => '<table style="width:100%; height:100%" cellspacing="0" cellpadding="0" >
        <tr>
          <td style="height:1.1in; border-right: 1px solid #e2e2e2; font-size: 20px; vertical-align: middle;" align="center">
              <div style="width:2.2in; margin-right:0.1in; line-height:24px; max-height:0.95in; overflow:hidden;">
                  [line1] - [line2]
                </div>
                <div style="font-size:16px; margin-top:0.03in;">[line3]</div>
            </td>
            <td style="vertical-align: center;" align="center">
              <div style="width:1.4in; overflow:hidden;">
                <img style="width:80%; margin-left:0.020in" src="' . $staticImageUrl . '"/>
                    <div style="font-size:16px; vertical-align:top" align="center"> Amazon Inc.</div>

              </div>
            </td>
        </tr>
        <tr>
            <td colspan="2" style="vertical-align: top;">
              <div style="height:0.55in; overflow:hidden; margin-top:0.08in;">
                <img style="width: 100%;" src="[barcode_img_url]"/>
              </div>
            </td>
        </tr>
        </table>',
    'is_default' => 1,
    'is_base' => 0,
    'height' => 2,
    'width' => 4,
    'uol_id' => 2,
    'fontStatus' => 1,
    'fontTagLink' => '<link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">',
    'fontCssRules' => "font-family: 'Roboto', sans-serif;",
    'base_padding_uol' => 0.15,
    'barcode_type' => 'C128',
  ),

  array(
    'name' => 'Example 2 - QRCode',
    'slug' => 'default-8',
    'template' => '<table style="width:100%; height:100%;" cellspacing="0" cellpadding="0">
        <tr>
          <td>
            <div >
              <img style="height: 0.9in; max-width:0.9in; margin-right:0.05in;" src="[barcode_img_url]"/>
            </div>
          </td>
          <td style="font-size: 14px; height:0.9in; width: 1.565in; overflow:hidden; text-align:center; vertical-align:middle">
            <div style="margin-bottom:0.04in; max-height:0.3in; overflow:hidden;"><b>[line1]</b></div>
            <div style="margin-bottom:0.03in; ">[line2]</div>
            <div style="margin-bottom:0.03in; ">[line3]</div>
            <div style="font-size:10px; max-height:0.35in; overflow:hidden;">[line4] </div>
          </td>
        </tr>
      </table>',
    'is_default' => 1,
    'is_base' => 0,
    'height' => 1,
    'width' => 2.625,
    'uol_id' => 2,
    'fontStatus' => 1,
    'fontTagLink' => '<link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">',
    'fontCssRules' => "font-family: 'Roboto', sans-serif;",
    'base_padding_uol' => 0.05,
    'barcode_type' => 'QRCODE',
  ),

  array(
    'name' => 'Example 3 - QRCode + Product Image',
    'slug' => 'default-9',
    'template' => '<table style="width:100%; height:100%; font-size:18px;" cellspacing="0" cellpadding="0">
        <tr>
          <td style="height:0.9in;" align="center">
            <img src="[product_image_url]" style="max-width:0.9in; max-height:0.9in;"/>
          </td>
          <td align="center">
            <div style="height: 0.39in; width: 2,1; overflow:hidden; margin:0 0.1in;">[line1]  </div>
            <div style="margin-bottom:0mm">[line2]</div>
            <div style="margin-bottom:0mm; font-size:16px;">[line3]</div>
            <div style="max-height:0.16in; width: 2,1in; overflow:hidden; font-size:16px; margin:0 0.1in;">[line4] dfg df</div>
          </td>
          <td align="center">
            <img style="width: 0.9in;" src="[barcode_img_url]"/>
          </td>
        </tr>
      </table>',
    'is_default' => 1,
    'is_base' => 0,
    'height' => 1,
    'width' => 4,
    'uol_id' => 2,
    'fontStatus' => 1,
    'fontTagLink' => '<link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">',
    'fontCssRules' => "font-family: 'Roboto', sans-serif;",
    'base_padding_uol' => 0.05,
    'barcode_type' => 'QRCODE',
  ),

  array(
    'name' => 'Example 4 - Vertical Barcode',
    'slug' => 'default-10',
    'template' => '<table style="width:100%; height:1.9in; font-size:16px;" cellspacing="0" cellpadding="0">
        <tr>
          <td align="center">
            <div style="width:2in; min-height:0.43in; max-height:0.50in; overflow:hidden; line-height:18px; margin-right:0.15in;">
              <div>[line1]</div>
            </div>
            <div style="width:2in; margin-top:0.08in; margin-right:0.15in;">[line2]</div>
          </td>
          <td style="height:100%; vertical-align:top; text-align:center;" rowspan="2">
            <div style="transform: rotate(270deg); transform-origin: 0% 0%; position:relative; width: 1.9in; top:100%;">

              <div style="height:0.5in; overflow:hidden;" >
                <img src="[barcode_img_url]" style="width:80%;"/>
              </div>

              <div style="font-size:14px; margin-top:0.04in; text-align:center;">[line3]</div>
            </div>
          </td>
        </tr>
        <tr>
          <td  style="text-align:center; font-size:15px;">
            <div style="width:2in; max-height:0.48in; overflow:hidden; margin-right:0.15in; ">[line4]</div>
          </td>
        </tr>
      </table>',
    'is_default' => 1,
    'is_base' => 0,
    'height' => 2,
    'width' => 3,
    'uol_id' => 2,
    'fontStatus' => 1,
    'fontTagLink' => '<link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">',
    'fontCssRules' => "font-family: 'Roboto', sans-serif;",
    'base_padding_uol' => 0.05,
    'barcode_type' => 'C128',
  ),

  array(
    'name' => 'Example 5 - Custom Fields & Attributes',
    'slug' => 'default-11',
    'template' => '<table style="width:100%; height:100%; font-size:18px;" cellspacing="0" cellpadding="0">
        <tr>
          <td style="line-height: 26px; width:1.9in;">
            <b>Woo Id</b>: [field=ID]<br/>
            <b>SKU</b>: [cf=_sku]<br/>
            <b>Sale price</b>: <br/>[cf=_sale_price]<br/>
            <b>Regular price</b>:<br/> <strike>[cf=_regular_price]</strike><br/>
          </td>
          <td style="line-height: 26px;" valign="top">
            <b>Size</b>: [attr=Size]<br/>
            <b>Color</b>: [attr=Color]<br/>
            <b>Category</b>: [category]
          </td>
        </tr>
        <tr>
          <td colspan="2">
            <div style="text-align:center; height:1in; overflow:hidden; margin-top:0.2in;">
              <img src="[barcode_img_url]" style="width:100%;"/>
            </div>
          </td>
        </tr>
        <tr>
          <td colspan="2">
            <div style="text-align:center; height:0.3in; overflow:hidden; margin-top:0.1in; font-size:22px">
              [line3]
            </div>
          </td>
        </tr>
      </table>',
    'is_default' => 1,
    'is_base' => 0,
    'height' => 3.33,
    'width' => 4,
    'uol_id' => 2,
    'fontStatus' => 1,
    'fontTagLink' => '<link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">',
    'fontCssRules' => "font-family: 'Roboto', sans-serif;",
    'base_padding_uol' => 0.15,
    'barcode_type' => 'C128',
  ),

  array(
    'name' => 'Example 6 - EAN/UPC',
    'slug' => 'default-12',
    'template' => '<table style="width:100%; height:100%;" cellspacing="0" cellpadding="0">
        <tr>
          <td style="text-align:center; vertical-align:middle;">
            <div>
              <div style="font-size:2mm; height:2mm; overflow:hidden; margin-top:0mm;">[line1]</div>
              <div style="font-size:2mm;  height:2mm; margin-bottom:0.7mm;margin-top:0.5mm;">Material: ▢ Plastic ▢ Wood</div>
              <img src="[barcode_img_url]" style="width: 28.22mm; height:20.57mm"/>
              <div style="font-size:1.2mm; height:1.43mm; overflow:hidden; margin-top:0.2mm;">
                Vendor: Some legal company Ltd. Address: Street 123, London, UK
              </div>
            </div>
          </td>
        </tr>
      </table>',
    'is_default' => 1,
    'is_base' => 0,
    'height' => 30,
    'width' => 40,
    'uol_id' => 1,
    'fontStatus' => 1,
    'fontTagLink' => '<link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">',
    'fontCssRules' => "font-family: 'Roboto', sans-serif;",
    'base_padding_uol' => 1,
    'barcode_type' => 'EAN13',
  ),

  array(
    'name' => 'Example 7 - vertical-align:middle',
    'slug' => 'default-14',
    'template' => '<div style="height: 15.5mm;overflow:hidden;" align="center">

  <!-- NAME, PRICE, SIZE, SKU -->
  
  <div style="float:left; height: 15.5mm; ">
    
    <div style="vertical-align:middle; display: table-cell; height: 15.5mm;">
      <div style=" width:35mm; overflow:hidden; font-size:14px;">
        <div style="max-height:7.9mm; overflow:hidden;">
          [line1]
        </div>
        <div style="white-space:nowrap;">[cf=_price][attr=color before=", "]</div>
        <div style="white-space:nowrap;">[cf=_sku]</div>
      </div>
    </div>
  </div>
  
  <!-- LOGO -->
  <div style="float:left; width:16.5mm; overflow:hidden;" >
    <img src="[product_image_url]" style="width:90%;"/>
  </div>
  
  <div style="clear:both;"></div>
</div>

 <!-- BARCODE -->
<div style="height:7mm; margin-top:0.5mm; overflow:hidden;">
    <img src="[barcode_img_url]" width="100%"/>
</div>
',
    'is_default' => 1,
    'is_base' => 0,
    'height' => 25,
    'width' => 54,
    'uol_id' => 1,
    'fontStatus' => 1,
    'fontTagLink' => '<link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">',
    'fontCssRules' => "font-family: 'Roboto', sans-serif;",
    'base_padding_uol' => 1,
    'barcode_type' => 'C128',
  ),

  array(
    'name' => 'Example 8 - vertical',
    'slug' => 'default-15',
    'template' => '<div style="width:52mm;height:23mm;transform: rotate(90deg);transform-origin: 11.5mm;">
  
  <div style="float:left; height: 15.5mm;" align="center">
    <div style="vertical-align:middle; display: table-cell; height: 15.5mm;">
      <div style=" width:35mm; overflow:hidden; font-size:14px;">
        <div style="max-height:7.9mm; overflow:hidden;">
          [line1]
        </div>
        <div style="white-space:nowrap;">[cf=_price][attr=color before=", "]</div>
        <div style="white-space:nowrap;">[cf=_sku]</div>
      </div>
    </div>
  </div>
  
  <div style="float:left; width:16.5mm; overflow:hidden;" >
    <img src="[product_image_url]" style="width:90%;"/>
  </div>
  
  <div style="clear:both;"></div>
  
  <div style="height:7mm; margin-top:0.5mm; overflow:hidden;">
      <img src="[barcode_img_url]" width="100%"/>
  </div>
</div>',
    'is_default' => 1,
    'is_base' => 0,
    'height' => 54,
    'width' => 25,
    'uol_id' => 1,
    'fontStatus' => 1,
    'fontTagLink' => '<link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">',
    'fontCssRules' => "font-family: 'Roboto', sans-serif;",
    'base_padding_uol' => 1,
    'barcode_type' => 'C128',
  ),

  array(
    'name' => 'Basic template',
    'slug' => 'default-16',
    'type' => 'shipping',
    'template' => $adaptiveTemplate,
    'is_default' => 1,
    'is_base' => 1,
    'height' => null,
    'width' => null,
    'uol_id' => 1,
    'fontStatus' => 1,
    'fontTagLink' => '<link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">',
    'fontCssRules' => "font-family: 'Roboto', sans-serif;",
  ),

  array(
    'name' => 'Shipping Label 10 x 8 cm',
    'slug' => 'default-13',
    'type' => 'shipping',
    'senderAddress' => 'Company Co.
Fake Street 123
New York
10001
USA',
    'template' => '<div style="width:94mm;">
  <!-- SHOP LOGO -->
  <div style="width:50%; float:left; height:20%;">
      <img src="[logo_image_url]" style="max-width:100%; max-height:100%;"/>
  </div>
  <!-- BARCODE -->
  <div style="width:45%; float:right;">
    <div style="height:11mm; overflow:hidden">
      <img src="[barcode_img_url]" style="width:100%"/>
    </div>
    <div style="text-align:center; font-size:14px;">
      [line1]
    </div>
  </div>
  <div style="clear:both;"></div>
  <div style="margin:10px 0 10px 0; border-top:1px solid #eeeeee;"></div>
  <!--FROM ADDRESS-->
  <div style="float:left; width:45%; font-size:14px;margin-right:10%; min-height:120px;">
    <div><b>FROM:</b></div>
    <div style="line-height:18px;">
      [shipping_sender_address]
    </div>
  </div>
  <!--TO ADDRESS-->
  <div style="float:left; width:45%; font-size:14px; min-height:120px">
    <div><b>SHIP TO:</b></div>
    <div style="line-height:18px;">
    [cf=_shipping_first_name|_billing_first_name] [cf=_shipping_last_name|_billing_last_name after="<br/>"]
    [cf=_shipping_address_1|_billing_address_1 after="<br/>"] 
    [cf=_shipping_address_2|_billing_address_2 after="<br/>"]
    [cf=_shipping_city|_billing_city after="<br/>"]
    [cf=_shipping_postcode|_billing_postcode after="<br/>"]
    [cf=_shipping_country|_billing_country] 
    </div>
  </div>
  <div style="clear:both;"></div>
  <fieldset style="margin:5px 0 10px 0; border:1px solid #eeeeee; height:21mm; 
              text-align:left; color:#ddd; padding-top:25px;">
    <legend style="font-size:12px">Place for a seal or notes</legend>

  </fieldset>
</div>',
    'is_default' => 1,
    'is_base' => 0,
    'height' => 80,
    'width' => 100,
    'uol_id' => 1,
    'fontStatus' => 1,
    'fontTagLink' => '<link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">',
    'fontCssRules' => "font-family: 'Roboto', sans-serif;",
    'base_padding_uol' => 3,
    'barcode_type' => '',
  ),

);
