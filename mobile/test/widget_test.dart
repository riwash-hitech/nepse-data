import 'package:flutter_test/flutter_test.dart';

import 'package:nepse_app/main.dart';

void main() {
  testWidgets('App boots to the welcome splash screen', (WidgetTester tester) async {
    await tester.pumpWidget(const NepseApp());
    await tester.pump();

    expect(find.text('Welcome to Riwash App'), findsOneWidget);
  });
}
