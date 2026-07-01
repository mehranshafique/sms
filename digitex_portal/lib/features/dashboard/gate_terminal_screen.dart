import 'package:flutter/material.dart';
import '../../config/theme.dart';
import '../../core/services/session_service.dart';
import 'widgets/menu_section.dart';
import 'widgets/welcome_banner.dart';

class GateTerminalScreen extends StatelessWidget {
  const GateTerminalScreen({super.key, required this.session, required this.onLogout});

  final SessionService session;
  final VoidCallback onLogout;

  @override
  Widget build(BuildContext context) {
    final ctx = session.context!;
    final tiles = ctx.menu.gateTerminal;

    return Scaffold(
      appBar: AppBar(
        title: Text(ctx.schoolName),
        actions: [
          TextButton.icon(
            onPressed: onLogout,
            icon: const Icon(Icons.logout, color: AppTheme.primary),
            label: const Text('Logout', style: TextStyle(color: AppTheme.primary)),
          ),
        ],
      ),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          WelcomeBanner(context: ctx, compact: true),
          const SizedBox(height: 8),
          Text(
            'Scan staff or student cards for attendance. Parents scan QR for pickup.',
            style: TextStyle(color: AppTheme.textMuted, fontSize: 13),
          ),
          const SizedBox(height: 20),
          MenuSection(
            title: 'Gate Terminal',
            crossAxisCount: 2,
            tiles: tiles,
            onTileTap: (tile) {
              ScaffoldMessenger.of(context).showSnackBar(
                SnackBar(content: Text('Open ${tile.title}')),
              );
            },
          ),
        ],
      ),
    );
  }
}
