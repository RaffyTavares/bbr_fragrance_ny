<?php
/**
 * BBR Fragance - Mail Service
 * Envio de emails usando PHPMailer con SMTP
 */

require_once __DIR__ . '/../lib/PHPMailer/Exception.php';
require_once __DIR__ . '/../lib/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/../lib/PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class MailService {

    private static function getMailer() {
        $db = getDB();
        $stmt = $db->prepare(
            "SELECT setting_key, setting_value FROM settings
             WHERE setting_key IN ('smtp_host','smtp_port','smtp_user','smtp_pass','smtp_from_name','smtp_from_email','store_name')"
        );
        $stmt->execute();
        $s = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        if (empty($s['smtp_host']) || empty($s['smtp_user']) || empty($s['smtp_pass'])) {
            return null;
        }

        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = $s['smtp_host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $s['smtp_user'];
        $mail->Password   = $s['smtp_pass'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = (int)($s['smtp_port'] ?: 587);
        $mail->CharSet    = 'UTF-8';

        $fromEmail = !empty($s['smtp_from_email']) ? $s['smtp_from_email'] : $s['smtp_user'];
        $fromName  = !empty($s['smtp_from_name']) ? $s['smtp_from_name'] : ($s['store_name'] ?? 'BBR Fragance');
        $mail->setFrom($fromEmail, $fromName);

        return $mail;
    }

    /**
     * Email de confirmacion de pedido al cliente
     */
    public static function sendOrderConfirmation($order) {
        try {
            $mail = self::getMailer();
            if (!$mail || empty($order['customer_email'])) return;

            $mail->addAddress($order['customer_email'], $order['customer_name']);
            $mail->isHTML(true);
            $mail->Subject = 'Confirmacion de Pedido ' . $order['order_number'] . ' - BBR Fragance';
            $mail->Body    = self::orderConfirmationTemplate($order);
            $mail->AltBody = "Tu pedido {$order['order_number']} ha sido recibido. Total: RD$ " . number_format($order['total'], 2);
            $mail->send();
        } catch (Exception $e) {
            error_log('MailService::sendOrderConfirmation failed: ' . $e->getMessage());
        }
    }

    /**
     * Email de actualizacion de estado del pedido
     */
    public static function sendOrderStatusUpdate($order, $newStatus) {
        try {
            $mail = self::getMailer();
            if (!$mail || empty($order['customer_email'])) return;

            $statusLabels = [
                'pending'    => 'Pendiente',
                'confirmed'  => 'Confirmado',
                'processing' => 'En Proceso',
                'shipped'    => 'Enviado',
                'delivered'  => 'Entregado',
                'cancelled'  => 'Cancelado',
            ];
            $statusLabel = $statusLabels[$newStatus] ?? $newStatus;

            $mail->addAddress($order['customer_email'], $order['customer_name']);
            $mail->isHTML(true);
            $mail->Subject = "Pedido {$order['order_number']} - Estado: {$statusLabel}";
            $mail->Body    = self::orderStatusTemplate($order, $newStatus, $statusLabel);
            $mail->AltBody = "Tu pedido {$order['order_number']} ha sido actualizado a: {$statusLabel}";
            $mail->send();
        } catch (Exception $e) {
            error_log('MailService::sendOrderStatusUpdate failed: ' . $e->getMessage());
        }
    }

    // =========================================================================
    // Templates HTML
    // =========================================================================

    private static function baseTemplate($title, $content) {
        return '<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"></head>
<body style="margin:0;padding:0;background:#111827;font-family:Arial,Helvetica,sans-serif">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#111827;padding:30px 0">
<tr><td align="center">
<table width="600" cellpadding="0" cellspacing="0" style="background:#1F2937;border-radius:12px;overflow:hidden;max-width:100%">
  <!-- Header -->
  <tr><td style="background:linear-gradient(135deg,#C9A96E,#B8941F);padding:24px;text-align:center">
    <h1 style="margin:0;color:#000;font-size:22px;font-weight:bold">BBR Fragance</h1>
    <p style="margin:6px 0 0;color:rgba(0,0,0,0.7);font-size:13px">' . htmlspecialchars($title) . '</p>
  </td></tr>
  <!-- Body -->
  <tr><td style="padding:30px;color:#D1D5DB;font-size:14px;line-height:1.6">' . $content . '</td></tr>
  <!-- Footer -->
  <tr><td style="padding:20px 30px;border-top:1px solid #374151;text-align:center;color:#6B7280;font-size:12px">
    <p style="margin:0">BBR Fragance - Santo Domingo, R.D.</p>
    <p style="margin:4px 0 0">Este es un email automatico, no responder directamente.</p>
  </td></tr>
</table>
</td></tr></table></body></html>';
    }

    private static function orderConfirmationTemplate($order) {
        $paymentLabels = ['cash' => 'Efectivo (Contra Entrega)', 'card' => 'Tarjeta (Al Entregar)', 'transfer' => 'Transferencia Bancaria', 'pending' => 'Por Definir'];
        $paymentLabel = $paymentLabels[$order['payment_method']] ?? $order['payment_method'];

        $items = '';
        if (!empty($order['items'])) {
            foreach ($order['items'] as $item) {
                $lineTotal = number_format((float)$item['subtotal'], 2);
                $unitPrice = number_format((float)$item['unit_price'], 2);
                $items .= '<tr>
                    <td style="padding:8px 0;border-bottom:1px solid #374151;color:#D1D5DB">' . htmlspecialchars($item['product_name']) . ' <span style="color:#9CA3AF;font-size:12px">' . htmlspecialchars($item['product_brand'] ?? '') .'</span></td>
                    <td style="padding:8px 0;border-bottom:1px solid #374151;text-align:center;color:#D1D5DB">' . $item['quantity'] . '</td>
                    <td style="padding:8px 0;border-bottom:1px solid #374151;text-align:right;color:#D1D5DB">RD$ ' . $unitPrice . '</td>
                    <td style="padding:8px 0;border-bottom:1px solid #374151;text-align:right;color:#C9A96E;font-weight:bold">RD$ ' . $lineTotal . '</td>
                </tr>';
            }
        }

        $content = '
        <p style="margin:0 0 20px;font-size:16px;color:#fff">Hola <strong>' . htmlspecialchars($order['customer_name']) . '</strong>,</p>
        <p style="margin:0 0 20px">Hemos recibido tu pedido correctamente. Aqui tienes el resumen:</p>

        <div style="background:#111827;border-radius:8px;padding:16px;margin-bottom:20px">
            <table width="100%" style="font-size:13px">
                <tr><td style="color:#9CA3AF;padding:4px 0">Pedido:</td><td style="text-align:right;color:#C9A96E;font-weight:bold">' . htmlspecialchars($order['order_number']) . '</td></tr>
                <tr><td style="color:#9CA3AF;padding:4px 0">Metodo de Pago:</td><td style="text-align:right;color:#D1D5DB">' . $paymentLabel . '</td></tr>
            </table>
        </div>

        <table width="100%" cellspacing="0" style="font-size:13px;margin-bottom:20px">
            <tr style="background:#374151">
                <th style="padding:8px;text-align:left;color:#C9A96E;font-size:12px">Producto</th>
                <th style="padding:8px;text-align:center;color:#C9A96E;font-size:12px">Cant</th>
                <th style="padding:8px;text-align:right;color:#C9A96E;font-size:12px">Precio</th>
                <th style="padding:8px;text-align:right;color:#C9A96E;font-size:12px">Subtotal</th>
            </tr>' . $items . '
        </table>

        <div style="background:#111827;border-radius:8px;padding:16px">
            <table width="100%" style="font-size:13px">
                <tr><td style="color:#9CA3AF;padding:4px 0">Subtotal:</td><td style="text-align:right;color:#D1D5DB">RD$ ' . number_format((float)$order['subtotal'], 2) . '</td></tr>
                <tr><td style="color:#9CA3AF;padding:4px 0">ITBIS:</td><td style="text-align:right;color:#D1D5DB">RD$ ' . number_format((float)$order['tax_amount'], 2) . '</td></tr>
                <tr><td style="color:#9CA3AF;padding:4px 0">Envio:</td><td style="text-align:right;color:#D1D5DB">RD$ ' . number_format((float)$order['shipping_cost'], 2) . '</td></tr>
                <tr><td colspan="2" style="border-top:1px solid #374151;padding-top:8px"></td></tr>
                <tr><td style="color:#fff;font-size:16px;font-weight:bold;padding:4px 0">Total:</td><td style="text-align:right;color:#C9A96E;font-size:16px;font-weight:bold">RD$ ' . number_format((float)$order['total'], 2) . '</td></tr>
            </table>
        </div>

        <p style="margin:20px 0 0;color:#9CA3AF;font-size:13px">Te notificaremos cuando el estado de tu pedido cambie. Gracias por tu compra!</p>';

        return self::baseTemplate('Confirmacion de Pedido', $content);
    }

    private static function orderStatusTemplate($order, $status, $statusLabel) {
        $statusColors = [
            'confirmed'  => '#3B82F6',
            'processing' => '#8B5CF6',
            'shipped'    => '#6366F1',
            'delivered'  => '#10B981',
            'cancelled'  => '#EF4444',
        ];
        $color = $statusColors[$status] ?? '#C9A96E';

        $messages = [
            'confirmed'  => 'Tu pedido ha sido confirmado y sera procesado pronto.',
            'processing' => 'Estamos preparando tu pedido.',
            'shipped'    => 'Tu pedido ha sido enviado! Pronto lo recibiras.',
            'delivered'  => 'Tu pedido ha sido entregado. Esperamos que disfrutes tu compra!',
            'cancelled'  => 'Tu pedido ha sido cancelado. Si tienes preguntas, contactanos.',
        ];
        $message = $messages[$status] ?? 'El estado de tu pedido ha sido actualizado.';

        $content = '
        <p style="margin:0 0 20px;font-size:16px;color:#fff">Hola <strong>' . htmlspecialchars($order['customer_name']) . '</strong>,</p>

        <div style="background:#111827;border-radius:8px;padding:20px;text-align:center;margin-bottom:20px">
            <p style="margin:0 0 8px;color:#9CA3AF;font-size:12px">PEDIDO ' . htmlspecialchars($order['order_number']) . '</p>
            <div style="display:inline-block;background:' . $color . ';color:#fff;padding:8px 20px;border-radius:20px;font-weight:bold;font-size:14px">' . htmlspecialchars($statusLabel) . '</div>
        </div>

        <p style="margin:0 0 20px">' . $message . '</p>

        <div style="background:#111827;border-radius:8px;padding:16px">
            <table width="100%" style="font-size:13px">
                <tr><td style="color:#9CA3AF;padding:4px 0">Total del Pedido:</td><td style="text-align:right;color:#C9A96E;font-weight:bold;font-size:16px">RD$ ' . number_format((float)$order['total'], 2) . '</td></tr>
            </table>
        </div>

        <p style="margin:20px 0 0;color:#9CA3AF;font-size:13px">Si tienes alguna pregunta, no dudes en contactarnos.</p>';

        return self::baseTemplate('Actualizacion de Pedido', $content);
    }
}
