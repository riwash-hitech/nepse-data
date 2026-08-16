import 'package:flutter/material.dart';
import '../services/api_client.dart';
import '../theme/app_theme.dart';

class StockDetailScreen extends StatefulWidget {
  final String symbol;
  const StockDetailScreen({super.key, required this.symbol});

  @override
  State<StockDetailScreen> createState() => _StockDetailScreenState();
}

class _StockDetailScreenState extends State<StockDetailScreen> {
  final _api = ApiClient.instance;
  Map<String, dynamic>? _data;
  bool _loading = true;
  String? _error;
  bool _watchlisting = false;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final res = await _api.get('/stocks/${widget.symbol}');
      setState(() {
        _data = Map<String, dynamic>.from(res);
        _loading = false;
      });
    } on ApiException catch (e) {
      setState(() {
        _error = e.message;
        _loading = false;
      });
    }
  }

  Future<void> _addToWatchlist() async {
    setState(() => _watchlisting = true);
    try {
      final stocks = await _api.get('/portfolio/stocks/search', {'q': widget.symbol});
      final match = (stocks as List).firstWhere(
        (s) => s['symbol'] == widget.symbol,
        orElse: () => null,
      );
      if (match == null) throw ApiException('Stock not found in local database.');
      await _api.post('/watchlist', {'stock_id': match['id']});
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('${widget.symbol} added to watchlist.')));
    } on ApiException catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
    } finally {
      if (mounted) setState(() => _watchlisting = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(widget.symbol),
        actions: [
          IconButton(icon: const Icon(Icons.refresh), onPressed: _load, tooltip: 'Refresh live data'),
          IconButton(
            icon: _watchlisting
                ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(strokeWidth: 2))
                : const Icon(Icons.star_border),
            onPressed: _watchlisting ? null : _addToWatchlist,
          ),
        ],
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _error != null
              ? Center(child: Padding(padding: const EdgeInsets.all(24), child: Text(_error!, textAlign: TextAlign.center)))
              : RefreshIndicator(onRefresh: _load, child: _buildBody(_data!)),
    );
  }

  Widget _buildBody(Map<String, dynamic> d) {
    final summary = d['market_summary'] as Map?;
    final signal = d['signal'] as Map?;
    final indicator = d['indicator'] as Map?;
    final pred30 = d['prediction_30d'] as Map?;

    final close = _num(summary?['close']);
    final change = _num(summary?['point_change']);
    final changePct = _num(summary?['percentage_change']);
    final isUp = (change ?? 0) >= 0;

    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        Text(d['name'] ?? widget.symbol, style: const TextStyle(fontSize: 16, color: AppColors.textSecondary)),
        const SizedBox(height: 4),
        Row(
          crossAxisAlignment: CrossAxisAlignment.end,
          children: [
            Text(close != null ? close.toStringAsFixed(2) : '—',
                style: const TextStyle(fontSize: 32, fontWeight: FontWeight.bold)),
            const SizedBox(width: 10),
            if (change != null)
              Padding(
                padding: const EdgeInsets.only(bottom: 6),
                child: Row(
                  children: [
                    Icon(isUp ? Icons.arrow_upward : Icons.arrow_downward,
                        size: 16, color: isUp ? AppColors.up : AppColors.down),
                    Text(
                      '${change.toStringAsFixed(2)} (${(changePct ?? 0).toStringAsFixed(2)}%)',
                      style: TextStyle(color: isUp ? AppColors.up : AppColors.down, fontWeight: FontWeight.w600),
                    ),
                  ],
                ),
              ),
          ],
        ),
        const SizedBox(height: 20),

        if (signal != null) _card('Signal', [
          _row('Signal', signal['signal_type']?.toString() ?? '—'),
          _row('Confidence', '${signal['confidence'] ?? '—'}%'),
        ]),

        if (indicator != null) _card('Indicators', [
          _row('RSI (14)', _fmt(indicator['rsi_14'])),
          if (indicator['sma_20'] != null) _row('SMA 20', _fmt(indicator['sma_20'])),
          if (indicator['sma_50'] != null) _row('SMA 50', _fmt(indicator['sma_50'])),
        ]),

        if (pred30 != null && pred30.isNotEmpty) _buildOutlookCard(pred30),

        const SizedBox(height: 8),
        Container(
          padding: const EdgeInsets.all(12),
          decoration: BoxDecoration(
            color: AppColors.surfaceAlt,
            borderRadius: BorderRadius.circular(10),
            border: Border.all(color: AppColors.border),
          ),
          child: const Text(
            'Statistical trend projection, not a guarantee of future profit. Always do your own research.',
            style: TextStyle(fontSize: 12, color: AppColors.textSecondary),
          ),
        ),
      ],
    );
  }

  Widget _buildOutlookCard(Map pred) {
    final direction = pred['direction']?.toString() ?? 'sideways';
    final color = direction == 'up'
        ? AppColors.up
        : direction == 'down'
            ? AppColors.down
            : AppColors.textSecondary;
    final label = direction == 'up' ? 'PROFIT LIKELY' : direction == 'down' ? 'LOSS LIKELY' : 'SIDEWAYS';

    return _card('30-Day Outlook', [
      Container(
        margin: const EdgeInsets.only(bottom: 10),
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
        decoration: BoxDecoration(color: color.withValues(alpha: 0.12), borderRadius: BorderRadius.circular(20)),
        child: Text(label, style: TextStyle(color: color, fontWeight: FontWeight.bold, fontSize: 12)),
      ),
      _row('Current price', _fmt(pred['current_price'])),
      _row('30D target', _fmt(pred['target_price'])),
      _row('Likely range', '${_fmt(pred['target_low'])} – ${_fmt(pred['target_high'])}'),
      _row('Predicted return', '${(pred['expected_return_pct'] ?? 0) >= 0 ? '+' : ''}${_fmt(pred['expected_return_pct'])}%'),
      _row('Confidence', '${pred['confidence'] ?? '—'}%'),
      _row('R²', _fmt(pred['r_squared'])),
    ]);
  }

  Widget _card(String title, List<Widget> children) {
    return Container(
      margin: const EdgeInsets.only(bottom: 14),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: AppColors.border),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(title, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15)),
          const SizedBox(height: 10),
          ...children,
        ],
      ),
    );
  }

  Widget _row(String label, String value) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 4),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(label, style: const TextStyle(color: AppColors.textSecondary)),
          Text(value, style: const TextStyle(fontWeight: FontWeight.w600)),
        ],
      ),
    );
  }

  double? _num(dynamic v) => v == null ? null : double.tryParse(v.toString());
  String _fmt(dynamic v) {
    final n = _num(v);
    return n == null ? '—' : n.toStringAsFixed(2);
  }
}
