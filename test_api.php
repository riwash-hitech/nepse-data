<?php
$cookieFile = tempnam(sys_get_temp_dir(), 'chukul_');
function r(string $u, string $cf): array {
    $ch=curl_init($u);
    curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>10,CURLOPT_SSL_VERIFYPEER=>false,
        CURLOPT_FOLLOWLOCATION=>true,CURLOPT_COOKIEFILE=>$cf,CURLOPT_COOKIEJAR=>$cf,
        CURLOPT_HTTPHEADER=>['Accept: application/json','User-Agent: Mozilla/5.0','Referer: https://chukul.com/']]);
    $b=curl_exec($ch);$c=curl_getinfo($ch,CURLINFO_HTTP_CODE);curl_close($ch);return[$c,$b];
}
r('https://chukul.com/', $cookieFile);

// Inspect historydata array structure
[$c,$b] = r('https://chukul.com/api/data/historydata/data/?symbol=NABIL', $cookieFile);
$d = json_decode($b, true);
echo "historydata keys: " . implode(', ', array_keys($d ?? [])) . "\n";
echo "t[0..2]: " . implode(', ', array_slice((array)($d['t']??[]), 0, 3)) . "\n";
echo "c[0..2]: " . implode(', ', array_slice((array)($d['c']??[]), 0, 3)) . "\n";
echo "total candles: " . count((array)($d['t']??[])) . "\n\n";

// Intraday data
[$c,$b] = r('https://chukul.com/api/data/newintrahistorydata/data/?symbol=NABIL', $cookieFile);
$d = json_decode($b, true);
echo "newintra keys: " . implode(', ', array_keys($d ?? [])) . "\n";
echo "cv = close value? sample: " . implode(', ', array_slice((array)($d['cv']??[]), 0, 3)) . "\n\n";

// Stock detail endpoint
[$c,$b] = r('https://chukul.com/api/stock/NABIL/', $cookieFile);
echo "stock/NABIL/ HTTP $c: " . substr($b, 0, 300) . "\n\n";

// Probe for live/today data
$probes = [
    '/api/live-trading/', '/api/livetrading/', '/api/live/today/',
    '/api/ticker/', '/api/price/today/', '/api/market/live/',
    '/api/data/live/', '/api/data/market-summary/', '/api/indices/',
    '/api/nepse-index/', '/api/summary/', '/api/market-summary/',
    '/api/stock/NABIL/live/', '/api/stock/NABIL/today/',
    '/api/data/today/', '/api/today/', '/api/data/price/',
    '/api/floorsheet/NABIL/', '/api/data/floorsheet/data/?symbol=NABIL',
    '/api/data/floorsheet/data/?symbol=NABIL&date=2026-05-28',
    '/api/stock/?sector=BANKING', '/api/stock/?sector=1',
];

echo "=== Live/today endpoint probes ===\n";
foreach ($probes as $path) {
    [$code, $body] = r('https://chukul.com' . $path, $cookieFile);
    if ($code !== 404 && $code !== 403) {
        $d = json_decode($body, true);
        $preview = is_array($d)
            ? (isset($d[0]) ? "array[" . count($d) . "] keys: " . implode(',', array_keys((array)$d[0]))
                            : "obj keys: " . implode(',', array_keys($d)))
            : substr($body, 0, 120);
        echo "[$code] $path\n    $preview\n";
    }
}
unlink($cookieFile);
unlink($cookieFile);
