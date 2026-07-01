import 'package:flutter/material.dart';
import 'config/theme.dart';
import 'core/api/api_client.dart';
import 'core/services/session_service.dart';
import 'features/auth/login_screen.dart';
import 'features/dashboard/dashboard_screen.dart';
import 'features/notifications/notifications_screen.dart';
import 'features/profile/profile_screen.dart';

void main() {
  runApp(const DigitexPortalApp());
}

class DigitexPortalApp extends StatefulWidget {
  const DigitexPortalApp({super.key});

  @override
  State<DigitexPortalApp> createState() => _DigitexPortalAppState();
}

class _DigitexPortalAppState extends State<DigitexPortalApp> {
  final _api = ApiClient();
  late final SessionService _session = SessionService(_api);
  bool _ready = false;
  bool _loggedIn = false;

  @override
  void initState() {
    super.initState();
    _bootstrap();
  }

  Future<void> _bootstrap() async {
    final ok = await _session.restore();
    setState(() {
      _ready = true;
      _loggedIn = ok;
    });
  }

  Future<void> _logout() async {
    await _session.logout();
    setState(() => _loggedIn = false);
  }

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'Digitex Portal',
      theme: AppTheme.light(),
      debugShowCheckedModeBanner: false,
      routes: {
        '/notifications': (_) => NotificationsScreen(session: _session),
        '/profile': (_) => ProfileScreen(session: _session),
      },
      home: !_ready
          ? const Scaffold(body: Center(child: CircularProgressIndicator()))
          : _loggedIn
              ? DashboardScreen(session: _session, onLogout: _logout)
              : LoginScreen(
                  session: _session,
                  onLoggedIn: () => setState(() => _loggedIn = true),
                ),
    );
  }
}
