import 'package:flutter/material.dart';
import 'adjust_holding_screen.dart';
import 'portfolio/overview_tab.dart';
import 'portfolio/holdings_tab.dart';
import 'portfolio/profit_loss_tab.dart';
import 'portfolio/realized_tab.dart';
import 'portfolio/transactions_tab.dart';

class PortfolioScreen extends StatefulWidget {
  const PortfolioScreen({super.key});

  @override
  State<PortfolioScreen> createState() => _PortfolioScreenState();
}

class _PortfolioScreenState extends State<PortfolioScreen> with TickerProviderStateMixin {
  late final TabController _tabController;

  final _overviewKey = GlobalKey<OverviewTabState>();
  final _holdingsKey = GlobalKey<HoldingsTabState>();
  final _profitLossKey = GlobalKey<ProfitLossTabState>();
  final _realizedKey = GlobalKey<RealizedTabState>();
  final _transactionsKey = GlobalKey<TransactionsTabState>();

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 5, vsync: this);
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  void _refreshCurrentTab() {
    switch (_tabController.index) {
      case 0:
        _overviewKey.currentState?.load();
        break;
      case 1:
        _holdingsKey.currentState?.load();
        break;
      case 2:
        _profitLossKey.currentState?.load();
        break;
      case 3:
        _realizedKey.currentState?.load();
        break;
      case 4:
        _transactionsKey.currentState?.load();
        break;
    }
  }

  Future<void> _openAdjust() async {
    final changed = await Navigator.of(context).push<bool>(
      MaterialPageRoute(builder: (_) => const AdjustHoldingScreen()),
    );
    if (changed == true) _refreshAll();
  }

  void _refreshAll() {
    _overviewKey.currentState?.load();
    _holdingsKey.currentState?.load();
    _profitLossKey.currentState?.load();
    _realizedKey.currentState?.load();
    _transactionsKey.currentState?.load();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Portfolio'),
        actions: [
          IconButton(icon: const Icon(Icons.refresh), onPressed: _refreshCurrentTab, tooltip: 'Refresh live data'),
          IconButton(icon: const Icon(Icons.add), onPressed: _openAdjust, tooltip: 'Buy / Sell'),
        ],
        bottom: TabBar(
          controller: _tabController,
          isScrollable: true,
          tabs: const [
            Tab(text: 'Overview'),
            Tab(text: 'Holdings'),
            Tab(text: 'Profit/Loss'),
            Tab(text: 'Realized'),
            Tab(text: 'Transactions'),
          ],
        ),
      ),
      body: TabBarView(
        controller: _tabController,
        children: [
          OverviewTab(key: _overviewKey),
          HoldingsTab(key: _holdingsKey),
          ProfitLossTab(key: _profitLossKey),
          RealizedTab(key: _realizedKey),
          TransactionsTab(key: _transactionsKey),
        ],
      ),
    );
  }
}
