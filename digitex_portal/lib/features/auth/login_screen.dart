import 'package:flutter/material.dart';
import '../../config/theme.dart';
import '../../core/services/session_service.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key, required this.session, required this.onLoggedIn});

  final SessionService session;
  final VoidCallback onLoggedIn;

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _email = TextEditingController();
  final _password = TextEditingController();
  bool _loading = false;
  String? _error;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: SafeArea(
        child: Center(
          child: SingleChildScrollView(
            padding: const EdgeInsets.all(24),
            child: ConstrainedBox(
              constraints: const BoxConstraints(maxWidth: 400),
              child: Column(
                children: [
                  Container(
                    width: 72,
                    height: 72,
                    decoration: BoxDecoration(
                      color: AppTheme.primary.withValues(alpha: 0.12),
                      shape: BoxShape.circle,
                    ),
                    child: const Icon(Icons.school, size: 36, color: AppTheme.primary),
                  ),
                  const SizedBox(height: 16),
                  const Text('Digitex Portal', style: TextStyle(fontSize: 24, fontWeight: FontWeight.bold)),
                  const SizedBox(height: 6),
                  const Text('Sign in with email, username, or staff ID', style: TextStyle(color: AppTheme.textMuted)),
                  const SizedBox(height: 28),
                  if (_error != null)
                    Padding(
                      padding: const EdgeInsets.only(bottom: 12),
                      child: Text(_error!, style: const TextStyle(color: Colors.red)),
                    ),
                  TextField(
                    controller: _email,
                    decoration: const InputDecoration(labelText: 'Email / Username / Staff ID'),
                    textInputAction: TextInputAction.next,
                  ),
                  const SizedBox(height: 14),
                  TextField(
                    controller: _password,
                    decoration: const InputDecoration(labelText: 'Password'),
                    obscureText: true,
                    onSubmitted: (_) => _submit(),
                  ),
                  const SizedBox(height: 24),
                  SizedBox(
                    width: double.infinity,
                    child: FilledButton(
                      onPressed: _loading ? null : _submit,
                      child: _loading
                          ? const SizedBox(width: 22, height: 22, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                          : const Text('Sign In'),
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }

  Future<void> _submit() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      await widget.session.login(_email.text.trim(), _password.text);
      widget.onLoggedIn();
    } catch (e) {
      setState(() => _error = e.toString());
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }
}
