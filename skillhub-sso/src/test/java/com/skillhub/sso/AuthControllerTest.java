package com.skillhub.sso;

import com.fasterxml.jackson.databind.ObjectMapper;
import com.skillhub.sso.dto.LoginRequest;
import com.skillhub.sso.dto.RegisterRequest;
import org.junit.jupiter.api.Test;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.boot.test.autoconfigure.web.servlet.AutoConfigureMockMvc;
import org.springframework.boot.test.context.SpringBootTest;
import org.springframework.security.crypto.password.PasswordEncoder;
import org.springframework.test.context.ActiveProfiles;
import org.springframework.test.context.DynamicPropertyRegistry;
import org.springframework.test.context.DynamicPropertySource;
import org.springframework.test.web.servlet.MockMvc;

import com.skillhub.sso.model.SsoUser;
import com.skillhub.sso.repository.SsoUserRepository;

import static org.springframework.test.web.servlet.request.MockMvcRequestBuilders.get;
import static org.springframework.test.web.servlet.request.MockMvcRequestBuilders.post;
import static org.springframework.test.web.servlet.result.MockMvcResultMatchers.*;

@SpringBootTest
@AutoConfigureMockMvc
@ActiveProfiles("test")
class AuthControllerTest {

    private static final String MASTER_KEY = "test-master-key";

    @Autowired
    private MockMvc mockMvc;

    @Autowired
    private SsoUserRepository userRepository;

    @Autowired
    private PasswordEncoder passwordEncoder;

    @Autowired
    private ObjectMapper objectMapper;

    @DynamicPropertySource
    static void ssoProperties(DynamicPropertyRegistry registry) {
        registry.add("sso.master-key", () -> MASTER_KEY);
        registry.add("sso.jwt.secret", () -> "test-secret-key-with-at-least-32-bytes!!");
        registry.add("sso.seed.enabled", () -> "false");
        registry.add("spring.datasource.url", () -> "jdbc:h2:mem:sso-test;DB_CLOSE_DELAY=-1");
    }

    private LoginRequest loginRequest(String email, String password) {
        LoginRequest request = new LoginRequest();
        request.setEmail(email);
        request.setPassword(password);
        return request;
    }

    @Test
    void login_sans_master_key_est_refuse_avec_403() throws Exception {
        mockMvc.perform(post("/api/auth/login")
                        .contentType("application/json")
                        .content(objectMapper.writeValueAsString(loginRequest("test@skillhub.test", "whatever"))))
                .andExpect(status().isForbidden());
    }

    @Test
    void login_avec_master_key_et_identifiants_valides_retourne_un_jwt() throws Exception {
        userRepository.save(new SsoUser(null, "formateur@skillhub.test",
                passwordEncoder.encode("Formateur123!"), "formateur"));

        mockMvc.perform(post("/api/auth/login")
                        .header("X-Master-Key", MASTER_KEY)
                        .contentType("application/json")
                        .content(objectMapper.writeValueAsString(loginRequest("formateur@skillhub.test", "Formateur123!"))))
                .andExpect(status().isOk())
                .andExpect(jsonPath("$.accessToken").isNotEmpty())
                .andExpect(jsonPath("$.role").value("formateur"));
    }

    @Test
    void register_cree_un_utilisateur_et_retourne_un_jwt() throws Exception {
        RegisterRequest request = new RegisterRequest();
        request.setEmail("nouveau@skillhub.test");
        request.setPassword("MotDePasse123!");
        request.setRole("apprenant");

        mockMvc.perform(post("/api/auth/register")
                        .header("X-Master-Key", MASTER_KEY)
                        .contentType("application/json")
                        .content(objectMapper.writeValueAsString(request)))
                .andExpect(status().isCreated())
                .andExpect(jsonPath("$.accessToken").isNotEmpty())
                .andExpect(jsonPath("$.email").value("nouveau@skillhub.test"));
    }

    @Test
    void register_avec_un_email_deja_utilise_est_refuse_avec_409() throws Exception {
        userRepository.save(new SsoUser(null, "existe@skillhub.test",
                passwordEncoder.encode("Existe123!"), "apprenant"));

        RegisterRequest request = new RegisterRequest();
        request.setEmail("existe@skillhub.test");
        request.setPassword("AutreMotDePasse123!");
        request.setRole("apprenant");

        mockMvc.perform(post("/api/auth/register")
                        .header("X-Master-Key", MASTER_KEY)
                        .contentType("application/json")
                        .content(objectMapper.writeValueAsString(request)))
                .andExpect(status().isConflict());
    }

    @Test
    void validate_avec_token_valide_retourne_les_claims() throws Exception {
        userRepository.save(new SsoUser(null, "apprenant@skillhub.test",
                passwordEncoder.encode("Apprenant123!"), "apprenant"));

        String body = mockMvc.perform(post("/api/auth/login")
                        .header("X-Master-Key", MASTER_KEY)
                        .contentType("application/json")
                        .content(objectMapper.writeValueAsString(loginRequest("apprenant@skillhub.test", "Apprenant123!"))))
                .andReturn().getResponse().getContentAsString();

        String token = objectMapper.readTree(body).get("accessToken").asText();

        mockMvc.perform(get("/api/auth/validate").header("Authorization", "Bearer " + token))
                .andExpect(status().isOk())
                .andExpect(jsonPath("$.valid").value(true))
                .andExpect(jsonPath("$.email").value("apprenant@skillhub.test"));
    }
}
