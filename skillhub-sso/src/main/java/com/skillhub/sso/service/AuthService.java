package com.skillhub.sso.service;

import com.skillhub.sso.dto.LoginRequest;
import com.skillhub.sso.dto.LoginResponse;
import com.skillhub.sso.dto.ValidateResponse;
import com.skillhub.sso.exception.InvalidCredentialsException;
import com.skillhub.sso.exception.InvalidMasterKeyException;
import com.skillhub.sso.model.SsoUser;
import com.skillhub.sso.repository.SsoUserRepository;
import io.jsonwebtoken.Claims;
import io.jsonwebtoken.JwtException;
import org.springframework.beans.factory.annotation.Value;
import org.springframework.security.crypto.password.PasswordEncoder;
import org.springframework.stereotype.Service;

@Service
public class AuthService {

    private final SsoUserRepository userRepository;
    private final PasswordEncoder passwordEncoder;
    private final JwtService jwtService;
    private final String masterKey;

    public AuthService(SsoUserRepository userRepository,
                        PasswordEncoder passwordEncoder,
                        JwtService jwtService,
                        @Value("${sso.master-key}") String masterKey) {
        this.userRepository = userRepository;
        this.passwordEncoder = passwordEncoder;
        this.jwtService = jwtService;
        this.masterKey = masterKey;
    }

    /**
     * Authentification forte : le client (le backend Laravel) doit prouver
     * qu'il s'agit d'un appelant de confiance en presentant la Master Key,
     * en plus des identifiants de l'utilisateur final. Sans Master Key valide,
     * la tentative echoue avant meme de verifier le mot de passe.
     */
    public LoginResponse login(LoginRequest request, String providedMasterKey) {
        if (providedMasterKey == null || !providedMasterKey.equals(masterKey)) {
            throw new InvalidMasterKeyException("Master Key manquante ou invalide");
        }

        SsoUser user = userRepository.findByEmail(request.getEmail())
                .orElseThrow(() -> new InvalidCredentialsException("Identifiants invalides"));

        if (!passwordEncoder.matches(request.getPassword(), user.getPassword())) {
            throw new InvalidCredentialsException("Identifiants invalides");
        }

        String token = jwtService.generateToken(user.getEmail(), user.getRole());
        return new LoginResponse(token, jwtService.getExpirationMs(), user.getEmail(), user.getRole());
    }

    public ValidateResponse validate(String token) {
        try {
            Claims claims = jwtService.parseClaims(token);
            return new ValidateResponse(true, claims.getSubject(), claims.get("role", String.class));
        } catch (JwtException ex) {
            throw ex;
        }
    }
}
