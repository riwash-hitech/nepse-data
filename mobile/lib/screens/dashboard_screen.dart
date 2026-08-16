import 'package:flutter/material.dart';
import '../services/api_client.dart';
import '../services/market_hours.dart';
import '../theme/app_theme.dart';
import '../widgets/bar_sparkline.dart';
import '../widgets/stock_avatar.dart';
import 'all_movers_screen.dart';
import 'stock_detail_screen.dart';
import 'stock_search_screen.dart';

class DashboardScreen extends StatefulWidget {
  final VoidCallback? onOpenPortfolio;
  const DashboardScreen({super.key, this.onOpenPortfolio});

  @override
  State<DashboardScreen> createState() => _DashboardScreenState();
}

class _DashboardScreenState extends State<DashboardScreen> {
  final _api = ApiClient.instance;

  List<dynamic> _indices = [];
  final Map<String, List<double>> _indexHistory = {};
  bool _indicesLoading = true;
  String? _indicesError;

  String _moversType = 'gainers';
  List<dynamic> _movers = [];
  bool _moversLoading = true;
  String? _moversError;

  @override
  void initState() {
    super.initState();
    _loadIndices();
    _loadMovers();
  }

  Future<void> _loadIndices() async {
    setState(() {
      _indicesLoading = true;
      _indicesError = null;
    });
    try {
      final res = await _api.get('/dashboard/indices');
      setState(() {
        _indices = res ?? [];
        _indicesLoading = false;
      });
      for (final ix in _indices) {
        final symbol = ix['symbol'] as String;
        try {
          final chart = await _api.get('/stocks/$symbol/chart', {'period': '1M'});
          final closes = (chart as List).map((p) => (p['close'] as num).toDouble()).toList();
          final last8 = closes.length > 8 ? closes.sublist(closes.length - 8) : closes;
          if (mounted) setState(() => _indexHistory[symbol] = last8);
        } catch (_) {}
      }
    } on ApiException catch (e) {
      setState(() {
        _indicesError = e.message;
        _indicesLoading = false;
      });
    }
  }

  Future<void> _loadMovers() async {
    setState(() {
      _moversLoading = true;
      _moversError = null;
    });
    try {
      final res = await _api.get('/dashboard/movers', {'type': _moversType, 'limit': 3});
      setState(() {
        _movers = (res as List?) ?? [];
        _moversLoading = false;
      });
    } on ApiException catch (e) {
      setState(() {
        _moversError = e.message;
        _moversLoading = false;
      });
    }
  }

  void _openStock(String symbol) {
    Navigator.of(context).push(MaterialPageRoute(builder: (_) => StockDetailScreen(symbol: symbol)));
  }

  @override
  Widget build(BuildContext context) {
    final marketOpen = isNepseMarketOpen();

    return Scaffold(
      body: SafeArea(
        child: RefreshIndicator(
          onRefresh: () async {
            await Future.wait([_loadIndices(), _loadMovers()]);
          },
          child: ListView(
            padding: const EdgeInsets.fromLTRB(16, 8, 16, 24),
            children: [
              _buildTopBar(),
              const SizedBox(height: 20),
              const Text('NEPSE Markets', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 24, color: AppColors.brand)),
              const SizedBox(height: 4),
              const Text(
                'Real-time overview of the Nepal Stock Exchange.',
                style: TextStyle(color: AppColors.textSecondary, fontSize: 13),
              ),
              const SizedBox(height: 10),
              _buildMarketBadge(marketOpen),
              const SizedBox(height: 18),
              _buildIndices(),
              const SizedBox(height: 24),
              _buildMoversHeader(),
              const SizedBox(height: 10),
              _buildMoversTable(),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildTopBar() {
    return Row(
      children: [
        IconButton(
          icon: const Icon(Icons.menu, color: AppColors.textPrimary),
          onPressed: widget.onOpenPortfolio,
          tooltip: 'Portfolio',
        ),
        Image.asset('assets/images/logo.png', width: 26, height: 26),
        const SizedBox(width: 8),
        const Text('Riwash Money', style: TextStyle(color: AppColors.brand, fontWeight: FontWeight.bold, fontSize: 17)),
        const Spacer(),
        IconButton(
          icon: const Icon(Icons.search, color: AppColors.textPrimary),
          tooltip: 'Search stocks',
          onPressed: () => Navigator.of(context).push(
            MaterialPageRoute(fullscreenDialog: true, builder: (_) => const StockSearchScreen()),
          ),
        ),
        IconButton(
          icon: const Icon(Icons.notifications_none, color: AppColors.textPrimary),
          tooltip: 'Notifications',
          onPressed: () => ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(content: Text('No new notifications.')),
          ),
        ),
      ],
    );
  }

  Widget _buildMarketBadge(bool open) {
    final color = open ? AppColors.up : AppColors.down;
    final bg = open ? AppColors.upBg : AppColors.downBg;
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
      decoration: BoxDecoration(color: bg, borderRadius: BorderRadius.circular(20)),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Container(width: 7, height: 7, decoration: BoxDecoration(color: color, shape: BoxShape.circle)),
          const SizedBox(width: 6),
          Text(open ? 'Market Open' : 'Market Closed', style: TextStyle(color: color, fontSize: 12, fontWeight: FontWeight.bold)),
        ],
      ),
    );
  }

  Widget _buildIndices() {
    if (_indicesLoading) return const Center(child: Padding(padding: EdgeInsets.all(20), child: CircularProgressIndicator()));
    if (_indicesError != null) return Text(_indicesError!, style: const TextStyle(color: AppColors.textSecondary));

    return Column(
      children: _indices.map((ix) {
        final change = (ix['change'] as num).toDouble();
        final changePct = (ix['change_percent'] as num).toDouble();
        final isUp = change >= 0;
        final color = isUp ? AppColors.up : AppColors.down;
        final bg = isUp ? AppColors.upBg : AppColors.downBg;
        final history = _indexHistory[ix['symbol']];

        return Container(
          margin: const EdgeInsets.only(bottom: 10),
          padding: const EdgeInsets.all(14),
          decoration: BoxDecoration(
            color: AppColors.surface,
            borderRadius: BorderRadius.circular(12),
            border: Border.all(color: AppColors.border),
          ),
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.center,
            children: [
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Text(
                          (ix['label'] ?? ix['symbol']).toString().toUpperCase(),
                          style: const TextStyle(color: AppColors.textSecondary, fontSize: 12, fontWeight: FontWeight.bold, letterSpacing: 0.3),
                        ),
                        const Spacer(),
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                          decoration: BoxDecoration(color: bg, borderRadius: BorderRadius.circular(20)),
                          child: Row(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              Icon(isUp ? Icons.trending_up : Icons.trending_down, size: 12, color: color),
                              const SizedBox(width: 2),
                              Text('${changePct >= 0 ? '+' : ''}${changePct.toStringAsFixed(2)}%',
                                  style: TextStyle(color: color, fontSize: 11, fontWeight: FontWeight.bold)),
                            ],
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 6),
                    Text((ix['close'] as num).toStringAsFixed(2),
                        style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 22, color: AppColors.textPrimary)),
                    Text('${change >= 0 ? '+' : ''}${change.toStringAsFixed(2)}', style: TextStyle(color: color, fontSize: 12)),
                  ],
                ),
              ),
              const SizedBox(width: 10),
              if (history != null)
                BarSparkline(closes: history, upColor: AppColors.up, mutedColor: AppColors.border, width: 96, height: 40)
              else
                const SizedBox(width: 96, height: 40),
            ],
          ),
        );
      }).toList(),
    );
  }

  Widget _buildMoversHeader() {
    Widget pill(String label, String value) {
      final selected = _moversType == value;
      return Padding(
        padding: const EdgeInsets.only(left: 8),
        child: OutlinedButton(
          onPressed: () {
            setState(() => _moversType = value);
            _loadMovers();
          },
          style: OutlinedButton.styleFrom(
            backgroundColor: selected ? AppColors.brand : AppColors.surface,
            foregroundColor: selected ? Colors.white : AppColors.textSecondary,
            side: const BorderSide(color: AppColors.border),
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
            minimumSize: Size.zero,
            tapTargetSize: MaterialTapTargetSize.shrinkWrap,
          ),
          child: Text(label, style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold)),
        ),
      );
    }

    return Row(
      children: [
        const Text('Top Movers', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 18, color: AppColors.brand)),
        const Spacer(),
        pill('Gainers', 'gainers'),
        pill('Losers', 'losers'),
      ],
    );
  }

  Widget _buildMoversTable() {
    return Container(
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: AppColors.border),
      ),
      child: Column(
        children: [
          if (_moversLoading)
            const Padding(padding: EdgeInsets.all(24), child: Center(child: CircularProgressIndicator()))
          else if (_moversError != null)
            Padding(padding: const EdgeInsets.all(16), child: Text(_moversError!, style: const TextStyle(color: AppColors.textSecondary)))
          else if (_movers.isEmpty)
            const Padding(padding: EdgeInsets.all(24), child: Center(child: Text('No data available.', style: TextStyle(color: AppColors.textSecondary))))
          else ...[
            const Padding(
              padding: EdgeInsets.symmetric(horizontal: 14, vertical: 10),
              child: Row(
                children: [
                  Expanded(flex: 3, child: Text('SYMBOL', style: TextStyle(color: AppColors.textSecondary, fontSize: 11, fontWeight: FontWeight.bold))),
                  Expanded(flex: 2, child: Text('LTP (NPR)', textAlign: TextAlign.right, style: TextStyle(color: AppColors.textSecondary, fontSize: 11, fontWeight: FontWeight.bold))),
                  Expanded(flex: 2, child: Text('CHANGE', textAlign: TextAlign.right, style: TextStyle(color: AppColors.textSecondary, fontSize: 11, fontWeight: FontWeight.bold))),
                ],
              ),
            ),
            const Divider(height: 1),
            ..._movers.map((m) => _moverRow(m)),
          ],
          InkWell(
            onTap: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => AllMoversScreen(initialType: _moversType))),
            child: const Padding(
              padding: EdgeInsets.symmetric(vertical: 14),
              child: Center(
                child: Text('View All Movers', style: TextStyle(color: AppColors.accent, fontWeight: FontWeight.bold, fontSize: 13)),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _moverRow(Map m) {
    final changePct = (m['change_percent'] as num).toDouble();
    final isUp = changePct >= 0;
    final color = isUp ? AppColors.up : AppColors.down;
    final bg = isUp ? AppColors.upBg : AppColors.downBg;

    return InkWell(
      onTap: () => _openStock(m['symbol']),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
        decoration: const BoxDecoration(border: Border(top: BorderSide(color: AppColors.border))),
        child: Row(
          children: [
            Expanded(
              flex: 3,
              child: Row(
                children: [
                  StockAvatar(symbol: m['symbol'] ?? '', size: 32),
                  const SizedBox(width: 10),
                  Expanded(
                    child: Text(m['symbol'] ?? '', style: const TextStyle(fontWeight: FontWeight.bold, color: AppColors.textPrimary), overflow: TextOverflow.ellipsis),
                  ),
                ],
              ),
            ),
            Expanded(
              flex: 2,
              child: Text((m['ltp'] as num).toStringAsFixed(2), textAlign: TextAlign.right, style: const TextStyle(color: AppColors.textPrimary, fontWeight: FontWeight.w600)),
            ),
            Expanded(
              flex: 2,
              child: Align(
                alignment: Alignment.centerRight,
                child: Container(
                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                  decoration: BoxDecoration(color: bg, borderRadius: BorderRadius.circular(20)),
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Icon(isUp ? Icons.arrow_upward : Icons.arrow_downward, size: 11, color: color),
                      Text('${changePct.abs().toStringAsFixed(1)}%', style: TextStyle(color: color, fontSize: 11, fontWeight: FontWeight.bold)),
                    ],
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
