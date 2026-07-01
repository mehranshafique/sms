/// API base URL without trailing slash. `/v1` is appended by [ApiClient].
class Env {
  static const String apiBaseUrl = String.fromEnvironment(
    'API_BASE_URL',
    defaultValue: 'https://account.digitexvx.com/api',
  );
}
