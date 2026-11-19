<?php

namespace App\Controllers;

use App\Libraries\AppSettings;
use Dompdf\Dompdf;
use Dompdf\Options;

class InvoiceDocuments extends BaseController
{
    public function printView(int $id, string $format)
    {
        if (! in_array($format, ['a4', 'thermal'], true)) throw new \CodeIgniter\Exceptions\PageNotFoundException('Format invoice tidak ditemukan.');
        $data = $this->invoiceData($id);
        if ($data instanceof \CodeIgniter\HTTP\ResponseInterface) return $data;
        return view('documents/invoice_' . $format, $data);
    }

    public function pdf(int $id)
    {
        $data = $this->invoiceData($id);
        if ($data instanceof \CodeIgniter\HTTP\ResponseInterface) return $data;
        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isHtml5ParserEnabled', true);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml(view('documents/invoice_a4', $data + ['pdfMode' => true]), 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $filename = preg_replace('/[^A-Za-z0-9._-]/', '-', $data['invoice']['invoice_no']) . '.pdf';
        return $this->response->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'inline; filename="' . $filename . '"')
            ->setBody($dompdf->output());
    }

    private function invoiceData(int $id)
    {
        if (! in_array(mynet_user('role'), ['superadmin', 'administrator', 'admin', 'keuangan', 'pelanggan'], true)) {
            return $this->response->setStatusCode(403)->setBody('Tidak memiliki akses.');
        }
        $query = db_connect()->table('invoices i')
            ->select('i.*, c.full_name, c.customer_code, c.phone AS customer_phone, c.email AS customer_email, c.address AS customer_address, p.name AS plan_name, p.service_type, b.name AS branch_name, b.phone AS branch_phone, b.address AS branch_address')
            ->join('customers c', 'c.id = i.customer_id')
            ->join('plans p', 'p.id = c.plan_id', 'left')
            ->join('branches b', 'b.id = i.branch_id', 'left')
            ->where('i.id', $id);
        if (mynet_user('role') === 'pelanggan') $query->where('i.customer_id', mynet_user('customer_id') ?? 0);
        elseif (mynet_user('branch_id')) $query->where('i.branch_id', mynet_user('branch_id'));
        $invoice = $query->get()->getRowArray();
        if (! $invoice) return $this->response->setStatusCode(404)->setBody('Tagihan tidak ditemukan.');
        $payments = db_connect()->table('payments')->select('provider, payment_method, amount, status, paid_at')->where('invoice_id', $id)->where('status', 'paid')->orderBy('id')->get()->getResultArray();
        $paid = array_sum(array_map(static fn (array $payment): float => (float) $payment['amount'], $payments));
        $settings = (new AppSettings())->all();
        $logoData = null;
        $logoUrl = (string) ($settings['logo_url'] ?? '');
        if (preg_match('#^/uploads/branding/[A-Za-z0-9._-]+\.(png|jpg)$#i', $logoUrl)) {
            $logoPath = FCPATH . ltrim(str_replace('/', DIRECTORY_SEPARATOR, $logoUrl), DIRECTORY_SEPARATOR);
            if (is_file($logoPath)) {
                $mime = match (strtolower(pathinfo($logoPath, PATHINFO_EXTENSION))) {'jpg' => 'image/jpeg', default => 'image/png'};
                $logoData = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($logoPath));
            }
        }
        return [
            'invoice' => $invoice, 'payments' => $payments, 'paid' => $paid,
            'balance' => max(0, (float) $invoice['total'] - $paid),
            'siteName' => $settings['site_name'] ?? 'Mynet Billing', 'siteTagline' => $settings['site_tagline'] ?? '',
            'publicPhone' => $settings['public_phone'] ?? '', 'logoData' => $logoData,
        ];
    }
}
