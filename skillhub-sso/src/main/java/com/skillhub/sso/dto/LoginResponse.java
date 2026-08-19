package com.skillhub.sso.dto;

public class LoginResponse {

    private final String tokenType = "Bearer";
    private final String accessToken;
    private final long expiresInMs;
    private final String email;
    private final String role;

    public LoginResponse(String accessToken, long expiresInMs, String email, String role) {
        this.accessToken = accessToken;
        this.expiresInMs = expiresInMs;
        this.email = email;
        this.role = role;
    }

    public String getTokenType() {
        return tokenType;
    }

    public String getAccessToken() {
        return accessToken;
    }

    public long getExpiresInMs() {
        return expiresInMs;
    }

    public String getEmail() {
        return email;
    }

    public String getRole() {
        return role;
    }
}
