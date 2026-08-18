<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LogViewerController extends Controller
{
    private const FILES = [
        'schedule' => 'schedule.log',
        'laravel'  => 'laravel.log',
    ];

    public function index(Request $request)
    {
        $file  = $request->get('file', 'schedule');
        $file  = array_key_exists($file, self::FILES) ? $file : 'schedule';
        $lines = max(20, min(1000, (int) $request->get('lines', 300)));

        $path = storage_path('logs/' . self::FILES[$file]);
        $content = is_file($path) ? $this->tail($path, $lines) : null;
        $size = is_file($path) ? filesize($path) : 0;
        $modified = is_file($path) ? filemtime($path) : null;

        return view('admin.logs.index', [
            'file'     => $file,
            'files'    => self::FILES,
            'lines'    => $lines,
            'content'  => $content,
            'size'     => $size,
            'modified' => $modified,
        ]);
    }

    /**
     * Reads only the last $lines lines without loading the whole file into
     * memory — log files can grow large over time.
     */
    private function tail(string $path, int $lines): string
    {
        $handle = fopen($path, 'r');
        if (!$handle) {
            return '';
        }

        $buffer = '';
        $chunkSize = 4096;
        $pos = filesize($path);
        $lineCount = 0;

        while ($pos > 0 && $lineCount <= $lines) {
            $readSize = min($chunkSize, $pos);
            $pos -= $readSize;
            fseek($handle, $pos);
            $chunk = fread($handle, $readSize);
            $buffer = $chunk . $buffer;
            $lineCount = substr_count($buffer, "\n");
        }

        fclose($handle);

        $allLines = explode("\n", $buffer);
        $tail = array_slice($allLines, -$lines);

        return implode("\n", $tail);
    }
}
