<!DOCTYPE html>
<html lang="nl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nieuw Contactbericht</title>
</head>

<body
    style="margin: 0; padding: 0; background-color: #141430; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center" width="100%"
        style="max-width: 600px; margin: 0 auto;">

        <!-- Header with gradient -->
        <tr>
            <td
                style="padding: 40px 30px; text-align: center; background: linear-gradient(135deg, #7c3aed 0%, #5b21b6 100%); border-radius: 12px 12px 0 0;">
                <h1 style="margin: 0; color: #ffffff; font-size: 28px; font-weight: 700; letter-spacing: -0.5px;">
                    Duidelijkheid.com
                </h1>
                <p style="margin: 10px 0 0; color: rgba(255,255,255,0.85); font-size: 14px;">
                    Nieuw contactbericht ontvangen
                </p>
            </td>
        </tr>

        <!-- Main Content -->
        <tr>
            <td style="background-color: #1e1e3f; padding: 30px;">

                <!-- Message Info Card -->
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%"
                    style="background-color: #2a2a4a; border-radius: 10px; overflow: hidden; margin-bottom: 20px;">
                    <tr>
                        <td style="padding: 25px;">
                            <!-- Greeting -->
                            <p style="margin: 0 0 20px; color: #a78bfa; font-size: 16px; font-weight: 600;">
                                📬 Van: {{ $m->name }}
                            </p>

                            <!-- Contact Details -->
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                <tr>
                                    <td style="padding: 12px 0; border-bottom: 1px solid rgba(167, 139, 250, 0.2);">
                                        <span
                                            style="color: #9ca3af; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">E-mail</span>
                                        <p style="margin: 5px 0 0; color: #e5e7eb; font-size: 15px;">
                                            <a href="mailto:{{ $m->email }}"
                                                style="color: #a78bfa; text-decoration: none;">{{ $m->email }}</a>
                                        </p>
                                    </td>
                                </tr>
                                @if($m->phone)
                                    <tr>
                                        <td style="padding: 12px 0; border-bottom: 1px solid rgba(167, 139, 250, 0.2);">
                                            <span
                                                style="color: #9ca3af; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">Telefoon</span>
                                            <p style="margin: 5px 0 0; color: #e5e7eb; font-size: 15px;">
                                                <a href="tel:{{ $m->phone }}"
                                                    style="color: #a78bfa; text-decoration: none;">{{ $m->phone }}</a>
                                            </p>
                                        </td>
                                    </tr>
                                @endif
                                <tr>
                                    <td style="padding: 12px 0;">
                                        <span
                                            style="color: #9ca3af; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">Onderwerp</span>
                                        <p style="margin: 5px 0 0; color: #ffffff; font-size: 16px; font-weight: 600;">
                                            {{ $m->subject }}</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>

                <!-- Message Content -->
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%"
                    style="background-color: #2a2a4a; border-radius: 10px; overflow: hidden; margin-bottom: 20px;">
                    <tr>
                        <td style="padding: 25px;">
                            <p
                                style="margin: 0 0 15px; color: #9ca3af; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">
                                Bericht</p>
                            <div style="color: #e5e7eb; font-size: 15px; line-height: 1.7; white-space: pre-wrap;">
                                {{ $m->message }}</div>
                        </td>
                    </tr>
                </table>

                <!-- CTA Button -->
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center"
                    style="margin: 30px auto;">
                    <tr>
                        <td
                            style="border-radius: 8px; background: linear-gradient(135deg, #7c3aed 0%, #5b21b6 100%); box-shadow: 0 4px 15px rgba(124, 58, 237, 0.35);">
                            <a href="{{ url('/hoofdbeheerder/contact/' . $m->id) }}" target="_blank"
                                style="display: inline-block; padding: 14px 32px; color: #ffffff; font-size: 15px; font-weight: 600; text-decoration: none; letter-spacing: 0.3px;">
                                💬 Bekijk in Dashboard
                            </a>
                        </td>
                    </tr>
                </table>

                <!-- Meta Info -->
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%"
                    style="background-color: rgba(42, 42, 74, 0.5); border-radius: 8px; border: 1px solid rgba(167, 139, 250, 0.15);">
                    <tr>
                        <td style="padding: 15px 20px;">
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                <tr>
                                    <td style="color: #6b7280; font-size: 12px;">
                                        <span style="color: #9ca3af;">📅</span>
                                        {{ optional($m->created_at)->format('d-m-Y H:i') }}
                                    </td>
                                    <td align="right" style="color: #6b7280; font-size: 12px;">
                                        <span
                                            style="display: inline-block; padding: 3px 10px; background-color: rgba(167, 139, 250, 0.2); color: #a78bfa; border-radius: 20px; font-size: 11px; font-weight: 500;">
                                            {{ ucfirst($m->status) }}
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>

            </td>
        </tr>

        <!-- Footer -->
        <tr>
            <td
                style="background-color: #141430; padding: 25px 30px; text-align: center; border-radius: 0 0 12px 12px; border-top: 1px solid rgba(167, 139, 250, 0.1);">
                <p style="margin: 0; color: #6b7280; font-size: 13px;">
                    © {{ date('Y') }} Duidelijkheid.com
                </p>
                <p style="margin: 8px 0 0; color: #4b5563; font-size: 11px;">
                    Deze email is automatisch verzonden. Antwoord direct naar de afzender.
                </p>
            </td>
        </tr>

    </table>

    <!-- Spacing for email clients -->
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
        <tr>
            <td style="height: 40px;"></td>
        </tr>
    </table>
</body>

</html>