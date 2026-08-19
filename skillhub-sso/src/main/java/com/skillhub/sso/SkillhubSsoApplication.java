package com.skillhub.sso;

import org.springframework.boot.SpringApplication;
import org.springframework.boot.autoconfigure.SpringBootApplication;
import org.springframework.boot.CommandLineRunner;
import org.springframework.beans.factory.annotation.Value;
import org.springframework.context.annotation.Bean;
import org.springframework.security.crypto.bcrypt.BCryptPasswordEncoder;
import org.springframework.security.crypto.password.PasswordEncoder;
import com.skillhub.sso.model.SsoUser;
import com.skillhub.sso.repository.SsoUserRepository;

@SpringBootApplication
public class SkillhubSsoApplication {

    public static void main(String[] args) {
        SpringApplication.run(SkillhubSsoApplication.class, args);
    }

    @Bean
    public PasswordEncoder passwordEncoder() {
        return new BCryptPasswordEncoder();
    }

    /**
     * Seed de demonstration : cree un utilisateur formateur et un utilisateur
     * apprenant si la base est vide, pour pouvoir tester /api/auth/login sans
     * dependre d'un outil d'administration.
     */
    @Bean
    public CommandLineRunner seedUsers(SsoUserRepository repository, PasswordEncoder encoder,
                                        @Value("${sso.seed.enabled:true}") boolean seedEnabled) {
        return args -> {
            if (!seedEnabled || repository.count() > 0) {
                return;
            }
            repository.save(new SsoUser(null, "formateur@skillhub.test", encoder.encode("Formateur123!"), "formateur"));
            repository.save(new SsoUser(null, "apprenant@skillhub.test", encoder.encode("Apprenant123!"), "apprenant"));
        };
    }
}
