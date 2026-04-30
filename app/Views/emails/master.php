<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f9fafb; margin: 0; padding: 0; }
        .wrapper { width: 100%; table-layout: fixed; background-color: #f9fafb; padding-bottom: 40px; }
        .main { background-color: #ffffff; margin: 0 auto; width: 100%; max-width: 600px; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); margin-top: 40px; }
        .header { background-color: #0A192F; padding: 30px; text-align: center; }
        .header h1 { color: #FACC15; margin: 0; font-size: 24px; letter-spacing: 1px; }
        .content { padding: 40px 30px; color: #374151; font-size: 16px; line-height: 1.6; }
        .footer { background-color: #f3f4f6; padding: 20px; text-align: center; font-size: 12px; color: #6b7280; }
        .btn { display: inline-block; background-color: #FACC15; color: #0A192F; padding: 12px 24px; text-decoration: none; font-weight: bold; border-radius: 6px; margin-top: 20px; margin-bottom: 20px;}
    </style>
</head>
<body>
    <table class="wrapper" width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td align="center">
                <table class="main" width="100%" cellpadding="0" cellspacing="0">
                    <!-- Header -->
                    <tr>
                        <td class="header">
                            <h1>LET THE BIBLE SPEAK</h1>
                        </td>
                    </tr>
                    
                    <!-- Dynamic Content -->
                    <tr>
                        <td class="content">
                            <?= $this->renderSection('content') ?>
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td class="footer">
                            <p>&copy; <?= date('Y') ?> Let The Bible Speak. All rights reserved.</p>
                            <p>This is an automated message, please do not reply directly to this email.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>