/**
 * Управление JWT токенами с проверкой срока действия
 */
export class TokenManager {
  constructor(config = {}) {
    this.config = {
      refreshThreshold: config.refreshThreshold || 300, // секунд
      ...config
    };
    this.tokens = null;
    this.domain = null;
  }

  setTokens(tokens, domain) {
    this.tokens = {
      accessToken: tokens.accessToken,
      refreshToken: tokens.refreshToken,
      expiresAt: this.calculateExpiresAt(tokens.accessToken),
      issuedAt: Date.now()
    };
    
    if (domain) {
      this.domain = domain;
    }
  }

  calculateExpiresAt(token) {
    try {
      // Декодируем JWT для получения exp
      const payload = JSON.parse(atob(token.split('.')[1]));
      return payload.exp * 1000; // Конвертируем в миллисекунды
    } catch (error) {
      // Fallback: токен истекает через час
      return Date.now() + 3600000;
    }
  }

  getTokens() {
    return this.tokens;
  }

  getDomain() {
    return this.domain;
  }

  clearTokens() {
    this.tokens = null;
    this.domain = null;
  }

  isValid() {
    if (!this.tokens?.accessToken) return false;
    
    const now = Date.now();
    const expiresAt = this.tokens.expiresAt;
    
    return expiresAt > now;
  }

  shouldRefresh() {
    if (!this.tokens?.accessToken) return false;
    
    const now = Date.now();
    const expiresAt = this.tokens.expiresAt;
    const threshold = this.config.refreshThreshold * 1000; // в миллисекунды
    
    return (expiresAt - now) < threshold;
  }

  getAccessToken() {
    return this.tokens?.accessToken;
  }

  getRefreshToken() {
    return this.tokens?.refreshToken;
  }

  getTokenExpiry() {
    if (!this.tokens?.expiresAt) return null;
    
    const now = Date.now();
    const expiresAt = this.tokens.expiresAt;
    return Math.max(0, Math.floor((expiresAt - now) / 1000));
  }
}