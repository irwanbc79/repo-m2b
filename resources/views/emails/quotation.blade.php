<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #333333;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        .email-wrapper {
            width: 100%;
            background-color: #f4f4f4;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .header {
            background-color: #0F2C59;
            color: #ffffff;
            padding: 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 900;
            letter-spacing: 1px;
        }
        .header p {
            margin: 5px 0 0;
            font-size: 12px;
            opacity: 0.8;
            text-transform: uppercase;
        }
        .content {
            padding: 30px;
        }
        .details-box {
            background-color: #f8f9fa;
            border-left: 4px solid #B91C1C;
            padding: 15px;
            margin: 20px 0;
            font-size: 14px;
        }
        .footer {
            background-color: #eeeeee;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #777;
        }
        .btn-wa {
            display: inline-block;
            background-color: #25D366;
            color: #ffffff;
            text-decoration: none;
            font-weight: bold;
            padding: 10px 20px;
            border-radius: 50px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="container">
            <div class="header">
                <h1>PT. MORA MULTI BERKAH</h1>
                <p>Logistic | Solution | Partner</p>
            </div>

            <div class="content">
                <p>Halo Pak/Bu <strong>{{ $customer_name }}</strong>,</p>
                
                <p>Semoga hari Anda menyenangkan.</p>

                <p>Menindaklanjuti diskusi kita mengenai kebutuhan <strong>{{ $service_name }}</strong>, bersama email ini kami lampirkan penawaran harga (Quotation) terbaik dari kami.</p>

                <p>Kami sudah sesuaikan skemanya agar prosesnya lebih efisien dan <em>cost-effective</em> untuk operasional Bapak/Ibu. Dokumen lengkap dapat dilihat pada file <strong>PDF</strong> yang terlampir.</p>

                <p>Jika ada poin yang perlu disesuaikan, jangan sungkan untuk menghubungi kami via balasan email ini atau langsung ke WhatsApp kantor kami:</p>

                <center>
                    <a href="https://wa.me/6281263027818" class="btn-wa" target="_blank">
                        Chat WhatsApp: +62 812 6302 7818
                    </a>
                </center>

                <br>
                <p>Terima kasih dan kami tunggu kabar baiknya.</p>

                <br>
                <p style="margin-bottom: 0;">Salam hangat,</p>
                <p style="margin-top: 5px;"><strong>Sales Department</strong><br>M2B Logistic</p>
            </div>

            <div class="footer">
                &copy; {{ date('Y') }} PT. Mora Multi Berkah.<br>
                Medan, Sumatera Utara - Indonesia.
            </div>
        </div>
    </div>
</body>
</html>