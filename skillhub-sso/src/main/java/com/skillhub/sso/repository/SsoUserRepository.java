package com.skillhub.sso.repository;

import com.skillhub.sso.model.SsoUser;
import org.springframework.data.jpa.repository.JpaRepository;

import java.util.Optional;

public interface SsoUserRepository extends JpaRepository<SsoUser, Long> {
    Optional<SsoUser> findByEmail(String email);
}
