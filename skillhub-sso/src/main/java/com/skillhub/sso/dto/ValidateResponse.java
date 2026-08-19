package com.skillhub.sso.dto;

public class ValidateResponse {

    private final boolean valid;
    private final String email;
    private final String role;

    public ValidateResponse(boolean valid, String email, String role) {
        this.valid = valid;
        this.email = email;
        this.role = role;
    }

    public boolean isValid() {
        return valid;
    }

    public String getEmail() {
        return email;
    }

    public String getRole() {
        return role;
    }
}
