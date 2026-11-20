<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use App\Services\ContentTransfer;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Super Admin page to download all website content as one file and load such a file
 * into this site (e.g. moving content from a PC to the live server).
 */
class ContentTransferController extends Controller
{
    public function __construct(private ContentTransfer $transfer, private AuditLogger $audit) {}

    public function index()
    {
        return view('admin.content-transfer', ['summary' => $this->transfer->summary()]);
    }

    public function export(Request $request)
    {
        $includeDemo = $request->boolean('include_demo');
        $file = $this->transfer->exportFile($includeDemo);
        $this->audit->log('content.exported', null, ['include_demo' => $includeDemo, 'bytes' => strlen($file)]);

        return response()->streamDownload(fn () => print ($file), 'innes-content-'.now()->format('Y-m-d-His').'.json.gz', [
            'Content-Type' => 'application/gzip',
        ]);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'max:'.(64 * 1024)],
            'confirm' => ['accepted'],
        ], [
            'confirm.accepted' => 'Tick the box to confirm that matching items on this site will be overwritten.',
        ], ['file' => 'export file']);

        $name = strtolower($request->file('file')->getClientOriginalName());
        if (! str_ends_with($name, '.json.gz') && ! str_ends_with($name, '.json')) {
            return back()->withErrors(['file' => 'Choose the .json.gz file downloaded from "Export content".']);
        }

        try {
            $data = $this->transfer->readFile((string) file_get_contents($request->file('file')->getPathname()));
            $result = $this->transfer->import($data);
        } catch (RuntimeException $e) {
            return back()->withErrors(['file' => 'Nothing was imported. '.$e->getMessage()]);
        }

        $this->audit->log('content.imported', null, ['counts' => $result]);

        return redirect()->route('admin.content-transfer.index')->with('import_result', $result)
            ->with('success', 'Content imported. Check the website and the admin pages to make sure everything looks right.');
    }
}
