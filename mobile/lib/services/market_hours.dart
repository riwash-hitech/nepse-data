/// Best-effort NEPSE trading-hours check — Sun-Thu, 11:00-15:00 Nepal time
/// (UTC+5:45). This is a schedule heuristic, not a live feed signal — NEPSE
/// closes early on some days and observes public holidays this doesn't know
/// about, so treat it as indicative only.
bool isNepseMarketOpen([DateTime? now]) {
  final nowUtc = (now ?? DateTime.now()).toUtc();
  final npt = nowUtc.add(const Duration(hours: 5, minutes: 45));

  // DateTime.weekday: Monday=1 ... Sunday=7. NEPSE trades Sunday-Thursday.
  final isTradingDay = npt.weekday == DateTime.sunday || npt.weekday == DateTime.monday || npt.weekday == DateTime.tuesday || npt.weekday == DateTime.wednesday || npt.weekday == DateTime.thursday;
  if (!isTradingDay) return false;

  final minutesSinceMidnight = npt.hour * 60 + npt.minute;
  return minutesSinceMidnight >= (11 * 60) && minutesSinceMidnight <= (15 * 60);
}
