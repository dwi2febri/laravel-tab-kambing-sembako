<?php

namespace App\Http\Controllers;

use App\Services\ReportService;
use App\Services\SavingService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\Response;

class ReportController extends Controller
{
    public function preview(Request $request, ReportService $service): View
    {
        Gate::authorize('admin');

        return view('reports.preview', ['report' => $service->data($request)]);
    }

    public function export(Request $request, string $format, ReportService $service): Response
    {
        Gate::authorize('admin');
        abort_unless(in_array($format, ['pdf', 'xlsx']), 404);
        $data = $service->data($request);
        SavingService::log($request->user(), 'report.exported', $data['title'].' ('.$format.')');
        if ($format === 'pdf') {
            return Pdf::loadView('reports.pdf', ['report' => $data])->setPaper('a4', 'landscape')->download($data['type'].'-'.today()->format('Y-m-d').'.pdf');
        }

        return response()->streamDownload(function () use ($data) {
            $spreadsheet = new Spreadsheet;
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Laporan');
            $rows = [$data['headers'], ...$data['rows']];
            foreach ($rows as $rowIndex => $row) {
                foreach ($row as $colIndex => $value) {
                    $cell = Coordinate::stringFromColumnIndex($colIndex + 1).($rowIndex + 1);
                    $sheet->setCellValueExplicit($cell, $value, is_int($value) || is_float($value) ? DataType::TYPE_NUMERIC : DataType::TYPE_STRING);
                }
            }
            $last = Coordinate::stringFromColumnIndex(count($data['headers']));
            $sheet->getStyle('A1:'.$last.'1')->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
            $sheet->getStyle('A1:'.$last.'1')->getFill()->setFillType('solid')->getStartColor()->setARGB('FF087F52');
            $sheet->freezePane('A2');
            $sheet->setAutoFilter('A1:'.$last.count($rows));
            for ($i = 1; $i <= count($data['headers']); $i++) {
                $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($i))->setAutoSize(true);
            }
            (new Xlsx($spreadsheet))->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, $data['type'].'-'.today()->format('Y-m-d').'.xlsx', ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }
}
