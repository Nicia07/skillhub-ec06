import React, { createContext, useContext, useEffect, useState, useCallback } from 'react';
import api from './api';

const AuthContext = createContext(null);

export const AuthProvider = ({ children }) => {
    const [user, setUser] = useState(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        const token = localStorage.getItem('jwt_token');
        if (!token) {
            setLoading(false);
            return;
        }
        api.get('/me')
            .then((res) => setUser(res.data))
            .catch(() => {
                localStorage.clear();
                setUser(null);
            })
            .finally(() => setLoading(false));
    }, []);

    const login = useCallback((token, userData) => {
        localStorage.setItem('jwt_token', token);
        localStorage.setItem('user_role', userData.role);
        localStorage.setItem('user_pseudo', userData.pseudo);
        setUser(userData);
    }, []);

    const logout = useCallback(() => {
        const token = localStorage.getItem('jwt_token');
        localStorage.clear();
        setUser(null);
        if (token) {
            // Explicit header: localStorage is already cleared, so the
            // request interceptor (which runs on the next microtask) would
            // otherwise send this call with no Authorization header.
            api.post('/logout', {}, { headers: { Authorization: `Bearer ${token}` } }).catch(() => {});
        }
    }, []);

    return (
        <AuthContext.Provider value={{ user, loading, isLoggedIn: !!user, login, logout }}>
            {children}
        </AuthContext.Provider>
    );
};

export const useAuth = () => useContext(AuthContext);
