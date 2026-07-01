import 'package:flutter/material.dart';
import '../../config/theme.dart';
import '../../core/services/session_service.dart';
import 'gate_terminal_screen.dart';
import 'widgets/welcome_banner.dart';
import 'widgets/menu_section.dart';

class DashboardScreen extends StatefulWidget {
  const DashboardScreen({super.key, required this.session, required this.onLogout});

  final SessionService session;
  final VoidCallback onLogout;

  @override
  State<DashboardScreen> createState() => _DashboardScreenState();
}

class _DashboardScreenState extends State<DashboardScreen> {
  int? _selectedChildId;

  @override
  Widget build(BuildContext context) {
    final ctx = widget.session.context!;
    if (ctx.isGateMode) {
      return GateTerminalScreen(session: widget.session, onLogout: widget.onLogout);
    }

    return Scaffold(
      appBar: AppBar(
        title: Row(
          children: [
            if (ctx.schoolLogo != null)
              Padding(
                padding: const EdgeInsets.only(right: 10),
                child: CircleAvatar(
                  radius: 18,
                  backgroundImage: NetworkImage(ctx.schoolLogo!),
                  backgroundColor: AppTheme.surface,
                ),
              ),
            Expanded(
              child: Text(ctx.schoolName, style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w600)),
            ),
          ],
        ),
        actions: [
          IconButton(
            icon: Badge(
              isLabelVisible: ctx.unreadNotifications > 0,
              label: Text('${ctx.unreadNotifications}'),
              child: const Icon(Icons.notifications_outlined),
            ),
            onPressed: () => Navigator.pushNamed(context, '/notifications'),
          ),
          if (ctx.hasMultiRole)
            PopupMenuButton<String>(
              icon: const Icon(Icons.swap_horiz),
              tooltip: 'Switch role',
              onSelected: (role) async {
                await widget.session.switchRole(role);
                if (mounted) setState(() {});
              },
              itemBuilder: (_) => ctx.switchableRoles
                  .map((r) => PopupMenuItem(
                        value: r,
                        child: Row(
                          children: [
                            if (r == ctx.activeRole) const Icon(Icons.check, size: 18, color: AppTheme.primary),
                            if (r == ctx.activeRole) const SizedBox(width: 8),
                            Text(r),
                          ],
                        ),
                      ))
                  .toList(),
            ),
          IconButton(icon: const Icon(Icons.person_outline), onPressed: () => Navigator.pushNamed(context, '/profile')),
          IconButton(icon: const Icon(Icons.logout), onPressed: widget.onLogout),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: () async {
          await widget.session.refreshContext();
          if (mounted) setState(() {});
        },
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            WelcomeBanner(context: ctx),
            if (ctx.children.isNotEmpty) ...[
              const SizedBox(height: 12),
              DropdownButtonFormField<int>(
                value: _selectedChildId ?? ctx.children.first['id'] as int?,
                decoration: const InputDecoration(labelText: 'Select child'),
                items: ctx.children
                    .map((c) => DropdownMenuItem<int>(
                          value: c['id'] as int,
                          child: Text(c['name'] as String? ?? ''),
                        ))
                    .toList(),
                onChanged: (v) => setState(() => _selectedChildId = v),
              ),
            ],
            if (!ctx.subscriptionActive) ...[
              const SizedBox(height: 12),
              Card(
                color: Colors.orange.shade50,
                child: const ListTile(
                  leading: Icon(Icons.warning_amber_rounded, color: Colors.orange),
                  title: Text('Subscription inactive'),
                  subtitle: Text('Some modules may be unavailable. Contact your school admin.'),
                ),
              ),
            ],
            if (ctx.menu.staffTools.isNotEmpty) ...[
              const SizedBox(height: 20),
              MenuSection(
                title: 'Staff Tools',
                tiles: ctx.menu.staffTools,
                onTileTap: (tile) => _openModule(context, tile.title),
              ),
            ],
            if (ctx.menu.studentPortal.isNotEmpty) ...[
              const SizedBox(height: 20),
              MenuSection(
                title: 'Student Portal',
                tiles: ctx.menu.studentPortal,
                onTileTap: (tile) => _openModule(context, tile.title),
              ),
            ],
          ],
        ),
      ),
    );
  }

  void _openModule(BuildContext context, String title) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text('$title — connect screen to existing API module')),
    );
  }
}
