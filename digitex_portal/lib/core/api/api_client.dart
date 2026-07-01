import 'dart:convert';
import 'package:http/http.dart' as http;
import '../config/env.dart';

class ApiClient {
  ApiClient({http.Client? client}) : _client = client ?? http.Client();

  final http.Client _client;
  String? _token;

  void setToken(String? token) => _token = token;

  Uri _uri(String path, [Map<String, String>? query]) {
    final base = Env.apiBaseUrl.replaceAll(RegExp(r'/+$'), '');
    final p = path.startsWith('/') ? path : '/$path';
    return Uri.parse('$base/v1$p').replace(queryParameters: query);
  }

  Map<String, String> get _headers => {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        if (_token != null) 'Authorization': 'Bearer $_token',
      };

  Future<Map<String, dynamic>> post(String path, [Map<String, dynamic>? body]) async {
    final res = await _client.post(_uri(path), headers: _headers, body: jsonEncode(body ?? {}));
    return _decode(res);
  }

  Future<Map<String, dynamic>> get(String path, [Map<String, String>? query]) async {
    final res = await _client.get(_uri(path, query), headers: _headers);
    return _decode(res);
  }

  Map<String, dynamic> _decode(http.Response res) {
    final data = res.body.isNotEmpty ? jsonDecode(res.body) as Map<String, dynamic> : <String, dynamic>{};
    if (res.statusCode >= 400) {
      throw ApiException(
        res.statusCode,
        data['message']?.toString() ?? 'Request failed (${res.statusCode})',
      );
    }
    return data;
  }
}

class ApiException implements Exception {
  ApiException(this.statusCode, this.message);
  final int statusCode;
  final String message;
  @override
  String toString() => message;
}
