<html>
<head>
</head>
<body>

<table width="800" border="0" cellpadding="0" cellspacing="0">

<!-- header -->
<tr>
    <td>
        <table width="800" border="0" cellpadding="0" cellspacing="0">
            <tr>
                <td><img src="{{HOST}}/email-images/header.gif" width="800" height="186"></td>
            </tr>
            <tr>
                <td>
                    <table width="100%" border="0" cellpadding="0" cellspacing="0">
                        <tr>
                            <td width="268"><img src="{{HOST}}/email-images/date_end.gif" width="268" height="34" alt=""
                                                 title="" border="0"/></td>
                            <td width="285"><img src="{{HOST}}/email-images/date_mid.gif" width="285" height="34" alt=""
                                                 title="" border="0"/></td>
                            <td width="100%" style="background-color:#44807e;" align="right"><span
                                    style="font-family:Arial, Helvetica, sans-serif;font-size: 12px;font-style: normal;font-weight: normal;font-variant: normal;text-transform: none;color: #FFFFFF;text-decoration: none;">{{DATE}}</span>
                            </td>
                            <td width="23"><img src="{{HOST}}/email-images/date_side.gif" width="23" height="34" alt=""
                                                title="" border="0"/></td>
                        </tr>
                        <tr>
                            <td width="268"><img src="{{HOST}}/email-images/heading_end.gif" width="268" height="31" alt=""
                                                 title="" border="0"/></td>
                            <td width="100%" style="background-color:#44807e;" colspan=2 align="right"><span
                                    style="font-family:Arial, Helvetica, sans-serif;font-size: 18px;font-style: normal;font-weight: normal;font-variant: normal;text-transform: none;color: #FFFFFF;text-decoration: none;">{{HEADER}}</span>
                            </td>
                            <td width="23"><img src="{{HOST}}/email-images/heading_side.gif" width="23" height="31" alt=""
                                                title="" border="0"/></td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td><img src="{{HOST}}/email-images/header_hor.gif" width="800" height="33" alt="" title="" border="0"/></td>
            </tr>
        </table>
    </td>
</tr>
<!-- end header -->

<tr valign="top">
    <td>
        <table width="800" height="100%" border="0" cellpadding="0" cellspacing="0">
            <tr valign="top" height="100%">
                <td width="293" height="100%">

                    <!-- sidebar -->
                    <table width="293" height="100%" border="0" cellpadding="0" cellspacing="0">
                        <tr valign="top" height="100%">
                            <td width="34" height="100%" valign="top" style="background-color:#699998"><img
                                    src="{{HOST}}/email-images/side_bar_top.gif" width="34" height="44" alt="" title=""
                                    border="0"/></td>
                            <td width="259" style="background-color:#699998;padding-right:20px;">
                                <table width="100%" border="0" cellpadding="0" cellspacing="0">
                                    <tr valign="top">
                                        <td style="padding-top:20px;padding-bottom:20px;"><span
                                                style="font-family:Arial, Helvetica, sans-serif;font-size: 18px;font-style: normal;font-weight: normal;font-variant: normal;text-transform: none;color: #FFFFFF;text-decoration: none;">{{SIDEHEADING}}</span>
                                        </td>
                                    </tr>
                                    {{SIDEBAR}}
                                    <tr valign="top">
                                        <td height="106"><img src="{{HOST}}/email-images/trans.gif" width="1" height="35"
                                                              alt="" title="" border="0"/></td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                    <!-- end sidebar -->

                </td>
                <td width="38"><img src="{{HOST}}/email-images/trans.gif" width="38" height="1" alt="" title="" border="0"/>
                </td>

                <!-- content -->
                <td width="469">
                    <table width="100%" border="0" cellpadding="0" cellspacing="0">
                        {{CONTENT}}
                    </table>
                </td>
                <!-- end content -->

            </tr>
        </table>
    </td>
</tr>

<!-- footer -->
<tr valign="top">
    <td><img src="{{HOST}}/email-images/footer.gif" width="800" height="149" alt="" title="" border="0" USEMAP="#footer">
        <map name="footer">
            <area shape="rect" coords="91,59,253,89" href="mailto:info@getthejob.co.za" alt="" title="">
            <area shape="rect" coords="284,62,444,84" href="http://www.getthejob.co.za" alt="" title="">
        </map>
    </td>
</tr>
<!-- end footer -->

<tr valign="top">
    <td align="center"><span
            style="font-family: Arial, Helvetica, sans-serif;font-size: 11px;font-style: normal;font-weight: normal;font-variant: normal;text-transform: none;color: #282651;text-decoration: none;">This is an automated email from <a
            href="http://getthejob.co.za" style="font-family: Arial, Helvetica, sans-serif;font-size: 11px;font-style: normal;font-weight: bold;font-variant: normal;text-transform: none;color: #407578;text-decoration: none;">GetTheJob.co.za</a>. <br>You can <a
            href="#" style="font-family: Arial, Helvetica, sans-serif;font-size: 11px;font-style: normal;font-weight: bold;font-variant: normal;text-transform: none;color: #407578;text-decoration: none;">adjust your mail settings here</a> or
            <a href="#" style="font-family: Arial, Helvetica, sans-serif;font-size: 11px;font-style: normal;font-weight: bold;font-variant: normal;text-transform: none;color: #407578;text-decoration: none;">unsubscribe</a> from all email.<br/>Having trouble viewing this email?
            <a href="#" style="font-family: Arial, Helvetica, sans-serif;font-size: 11px;font-style: normal;font-weight: bold;font-variant: normal;text-transform: none;color: #407578;text-decoration: none;">Click here to view it online.</a></span></td>
</tr>
</table>
</body>
</html>