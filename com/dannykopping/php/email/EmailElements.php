<?php

    class EmailElements
    {
        const SIDEBAR_ITEM = <<<EOT
<tr valign="top">
    <td><span
            style="font-family:Arial, Helvetica, sans-serif;font-size: 15px;font-style: normal;font-weight: bold;font-variant: normal;text-transform: none;color: #2a2852;text-decoration: none;">{{TITLE}}</span><br><span
            style="font-family:Arial, Helvetica, sans-serif;font-size: 13px;font-style: normal;font-weight: normal;font-variant: normal;text-transform: none;color: #FFFFFF;text-decoration: none;">{{CONTENT}}</span>
</tr>
EOT;

        const SIDEBAR_ITEM_SEPARATOR = <<<EOT
<tr valign="top">
    <td><img src="{{HOST}}/email-images/side_hor.gif" width="221" height="23" alt=""
             title="" border="0"/></td>
</tr>
EOT;

        const CONTENT_ITEM = <<<EOT
<tr valign="top">
    <td style="padding-top:25px;"><span
            style="font-family:Arial, Helvetica, sans-serif;font-size: 14px;font-style: normal;font-weight: bold;font-variant: normal;text-transform: none;color: #44807e;text-decoration: none;">{{TITLE}}</span>
    </td>
</tr>
<tr valign="top">
    <td><img src="{{HOST}}/email-images/HR_SHORT.gif" width="469" height="16" alt="" title=""
             border="0"/></td>
</tr>
<tr valign="top">
    <td><span
            style="font-family:Arial, Helvetica, sans-serif;font-size: 12px;font-style: normal;font-weight: normal;font-variant: normal;text-transform: none;color: #2a2852;text-decoration: none;">{{CONTENT}}</span>
</tr>
EOT;

    }
?>