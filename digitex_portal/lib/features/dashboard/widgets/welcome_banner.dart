import 'package:flutter/material.dart';
import '../../config/theme.dart';
import '../../core/models/mobile_context.dart';

class WelcomeBanner extends StatelessWidget {
  const WelcomeBanner({super.key, required this.context, this.compact = false});

  final MobileContext context;
  final bool compact;

  @override
  Widget build(BuildContext buildContext) {
    return Container(
      padding: EdgeInsets.all(compact ? 14 : 18),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          colors: [AppTheme.primary, AppTheme.primaryDark],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(16),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            'Welcome, ${context.name}',
            style: const TextStyle(color: Colors.white, fontSize: 18, fontWeight: FontWeight.bold),
          ),
          const SizedBox(height: 4),
          Text(
            'at ${context.schoolName}${context.sessionName != null ? ' (${context.sessionName})' : ''}',
            style: TextStyle(color: Colors.white.withValues(alpha: 0.9), fontSize: 13),
          ),
          if (!compact) ...[
            const SizedBox(height: 8),
            Wrap(
              spacing: 8,
              runSpacing: 6,
              children: [
                _chip('Role: ${context.activeRole}'),
                if (context.planName != null) _chip(context.planName!),
                if (context.capabilities['multi_role'] == true) _chip('Multi-role'),
              ],
            ),
          ],
        ],
      ),
    );
  }

  Widget _chip(String label) => Container(
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
        decoration: BoxDecoration(
          color: Colors.white.withValues(alpha: 0.18),
          borderRadius: BorderRadius.circular(20),
        ),
        child: Text(label, style: const TextStyle(color: Colors.white, fontSize: 11)),
      );
}
