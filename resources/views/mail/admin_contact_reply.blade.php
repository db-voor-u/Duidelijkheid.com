<!DOCTYPE html>
<html lang="nl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bericht van Duidelijkheid.com</title>
</head>

<body
    style="margin: 0; padding: 0; background-color: #f3f4f6; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center" width="100%"
        style="max-width: 600px; margin: 0 auto;">

        <!-- Spacer -->
        <tr>
            <td style="height: 30px;"></td>
        </tr>

        <!-- Header -->
        <tr>
            <td
                style="padding: 30px 40px; text-align: center; background: linear-gradient(135deg, #7c3aed 0%, #5b21b6 100%); border-radius: 16px 16px 0 0;">
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center">
                    <tr>
                        <td style="vertical-align: middle;">
                            <h1
                                style="margin: 0; color: #ffffff; font-size: 24px; font-weight: 700; letter-spacing: -0.5px;">
                                Duidelijkheid.com
                            </h1>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>

        <!-- Main Content -->
        <tr>
            <td
                style="background-color: #ffffff; padding: 40px; border-left: 1px solid #e5e7eb; border-right: 1px solid #e5e7eb;">

                <!-- Message Body -->
                <div style="color: #374151; font-size: 16px; line-height: 1.8;">
                    {!! $body !!}
                </div>

                @if(isset($attachmentName) && $attachmentName)
                    <!-- Attachment Section -->
                    <div style="margin-top: 30px; padding-top: 25px; border-top: 1px solid #e5e7eb;">
                        <p
                            style="margin: 0 0 15px; color: #6b7280; font-size: 13px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">
                            📎 Bijlage
                        </p>

                        <!-- Document Preview Card -->
                        <table role="presentation" cellspacing="0" cellpadding="0" border="0"
                            style="border-radius: 12px; overflow: hidden; border: 1px solid #e5e7eb; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
                            <tr>
                                <!-- Document Preview Icon -->
                                <td
                                    style="background: linear-gradient(135deg, #7c3aed 0%, #5b21b6 100%); padding: 25px 30px; text-align: center; vertical-align: middle;">
                                    <table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center">
                                        <tr>
                                            <td
                                                style="background-color: #ffffff; border-radius: 8px; padding: 15px 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                                                <!-- PDF Icon -->
                                                <table role="presentation" cellspacing="0" cellpadding="0" border="0"
                                                    align="center">
                                                    <tr>
                                                        <td style="font-size: 32px; line-height: 1;">📄</td>
                                                    </tr>
                                                    <tr>
                                                        <td style="padding-top: 5px;">
                                                            <span
                                                                style="background-color: #dc2626; color: #ffffff; font-size: 10px; font-weight: 700; padding: 2px 6px; border-radius: 3px; text-transform: uppercase;">PDF</span>
                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                                <!-- Document Info -->
                                <td style="background-color: #f9fafb; padding: 20px 25px; vertical-align: middle;">
                                    <p style="margin: 0 0 5px; color: #111827; font-size: 15px; font-weight: 600;">
                                        {{ $attachmentName }}
                                    </p>
                                    <p style="margin: 0 0 12px; color: #6b7280; font-size: 13px;">
                                        Document bijlage
                                    </p>
                                    <p style="margin: 0; color: #7c3aed; font-size: 13px; font-weight: 500;">
                                        ⬇️ Zie bijlage hieronder om te downloaden
                                    </p>
                                </td>
                            </tr>
                        </table>
                    </div>
                @endif

                <!-- Standard Greeting -->
                <div style="margin-top: 35px; padding-top: 25px; border-top: 1px solid #e5e7eb;">
                    <p style="margin: 0; color: #374151; font-size: 16px; line-height: 1.6;">
                        Met vriendelijke groet,
                    </p>
                    <p style="margin: 8px 0 0; color: #7c3aed; font-size: 16px; font-weight: 600;">
                        Team Duidelijkheid
                    </p>
                </div>

            </td>
        </tr>

        <!-- Footer -->
        <tr>
            <td
                style="background-color: #1e1b4b; padding: 25px 40px; text-align: center; border-radius: 0 0 16px 16px;">
                <p style="margin: 0 0 10px; color: rgba(255,255,255,0.9); font-size: 14px; font-weight: 500;">
                    Duidelijkheid.com
                </p>
                <p style="margin: 0; color: rgba(255,255,255,0.5); font-size: 12px;">
                    © {{ date('Y') }} Alle rechten voorbehouden
                </p>
            </td>
        </tr>

        <!-- Bottom Spacer -->
        <tr>
            <td style="height: 30px;"></td>
        </tr>

    </table>
</body>

</html>