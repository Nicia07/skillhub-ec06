package com.skillhub.sso.controller;

import com.skillhub.sso.dto.LoginRequest;
import com.skillhub.sso.dto.LoginResponse;
import com.skillhub.sso.dto.RegisterRequest;
import com.skillhub.sso.dto.ValidateResponse;
import com.skillhub.sso.exception.InvalidCredentialsException;
import com.skillhub.sso.service.AuthService;
import jakarta.validation.Valid;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.*;

@RestController
@RequestMapping("/api/auth")
public class AuthController {

    private static final String MASTER_KEY_HEADER = "X-Master-Key";

    private final AuthService authService;

    public AuthController(AuthService authService) {
        this.authService = authService;
    }

    @PostMapping("/login")
    public ResponseEntity<LoginResponse> login(@Valid @RequestBody LoginRequest request,
                                                @RequestHeader(value = MASTER_KEY_HEADER, required = false) String masterKey) {
        LoginResponse response = authService.login(request, masterKey);
        return ResponseEntity.ok(response);
    }

    @PostMapping("/register")
    public ResponseEntity<LoginResponse> register(@Valid @RequestBody RegisterRequest request,
                                                   @RequestHeader(value = MASTER_KEY_HEADER, required = false) String masterKey) {
        LoginResponse response = authService.register(request, masterKey);
        return ResponseEntity.status(201).body(response);
    }

    @GetMapping("/validate")
    public ResponseEntity<ValidateResponse> validate(@RequestHeader(value = "Authorization", required = false) String authorization) {
        if (authorization == null || !authorization.startsWith("Bearer ")) {
            throw new InvalidCredentialsException("En-tete Authorization Bearer manquant");
        }
        String token = authorization.substring("Bearer ".length());
        return ResponseEntity.ok(authService.validate(token));
    }
}
