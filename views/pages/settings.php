<link rel="stylesheet" href="<?= site_url() ?>/assets/css/configuracao.css">
    <!-- Breadcrumb -->
    <div class="breadcrumb-container">
    <div class="breadcrumb-content">
        <nav class="breadcrumb">
            <a href="index.html" class="breadcrumb-item">
                <i class="fa fa-home"></i>
                Início
            </a>
            <span class="breadcrumb-separator">›</span>
            <a href="perfil.html" class="breadcrumb-item">Perfil</a>
            <span class="breadcrumb-separator">›</span>
            <span class="breadcrumb-item current">Configurações</span>
        </nav>
        
        <div class="page-actions">
            <button class="action-btn preview-btn" title="Visualizar Perfil" id="previewProfile">
                <i class="fa fa-eye"></i>
                Preview
            </button>
            <button class="action-btn reset-btn" title="Restaurar Padrões" id="resetSettings">
                <i class="fa fa-undo"></i>
                Restaurar
            </button>
        </div>
    </div>
</div>

<!-- Main Container -->
<main class="profile-settings-container">
    <!-- Settings Content -->
    <div class="settings-content">
        <!-- Page Header -->
        <div class="settings-header">
            <div class="header-content">
                <div class="header-icon">
                    <i class="fa fa-user-cog"></i>
                </div>
                <div class="header-text">
                    <h1>Configurações do Perfil</h1>
                    <p>Personalize seu perfil e configure suas preferências na comunidade</p>
                </div>
            </div>
            
            <div class="settings-progress">
                <div class="progress-bar">
                    <div class="progress-fill" id="profileProgress"></div>
                </div>
                <span class="progress-text">
                    <span id="progressPercentage">65</span>% do perfil completo
                </span>
            </div>
        </div>

        <!-- Settings Form -->
        <form id="profileSettingsForm" class="settings-form">
            <!-- Avatar & Basic Info Section -->
            <div class="settings-section avatar-section">
                <div class="section-header">
                    <h2>
                        <i class="fa fa-image"></i>
                        Foto de Perfil & Informações Básicas
                    </h2>
                    <span class="section-status incomplete" id="basicInfoStatus">
                        <i class="fa fa-exclamation-circle"></i>
                        Incompleto
                    </span>
                </div>
                
                <div class="section-content">
                    <div class="avatar-upload-section">
                        <div class="current-avatar">
                            <img src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTIwIiBoZWlnaHQ9IjEyMCIgdmlld0JveD0iMCAwIDEyMCAxMjAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxyZWN0IHdpZHRoPSIxMjAiIGhlaWdodD0iMTIwIiBmaWxsPSJ1cmwoI2dyYWRpZW50KSIvPgo8ZGVmcz4KPGxpbmVhckdyYWRpZW50IGlkPSJncmFkaWVudCIgeDE9IjAlIiB5MT0iMCUiIHgyPSIxMDAlIiB5Mj0iMTAwJSI+CjxzdG9wIG9mZnNldD0iMCUiIHN0b3AtY29sb3I9IiNGRjAwN0YiLz4KPHN0b3Agb2Zmc2V0PSIxMDAlIiBzdG9wLWNvbG9yPSIjMDBCRkZGIi8+CjwvbGluZWFyR3JhZGllbnQ+CjwvZGVmcz4KPHR4dCB4PSI2MCIgeT0iNjUiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIxNCIgZmlsbD0id2hpdGUiIHRleHQtYW5jaG9yPSJtaWRkbGUiPkF2YXRhcjwvdGV4dD4KPC9zdmc+" alt="Avatar atual" id="currentAvatar">
                            <div class="avatar-overlay">
                                <i class="fa fa-camera"></i>
                            </div>
                        </div>
                        
                        <div class="avatar-actions">
                            <button type="button" class="btn btn-primary upload-btn" id="uploadAvatarBtn">
                                <i class="fa fa-upload"></i>
                                Enviar Nova Foto
                            </button>
                            <button type="button" class="btn btn-outline remove-btn" id="removeAvatarBtn">
                                <i class="fa fa-trash"></i>
                                Remover
                            </button>
                            <input type="file" id="avatarInput" accept="image/*" style="display: none;">
                        </div>
                        
                        <div class="avatar-guidelines">
                            <h4>Diretrizes da foto:</h4>
                            <ul>
                                <li>Formato: JPG, PNG ou GIF</li>
                                <li>Tamanho máximo: 5MB</li>
                                <li>Dimensão recomendada: 400x400px</li>
                                <li>Evite conteúdo ofensivo</li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="basic-info-grid">
                        <div class="form-group">
                            <label for="username">Nome de usuário *</label>
                            <input type="text" id="username" name="username" value="Sininhofunny" maxlength="20" required>
                            <div class="field-info">
                                <span class="char-counter">10/20</span>
                                <span class="field-status available">
                                    <i class="fa fa-check"></i>
                                    Disponível
                                </span>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="displayName">Nome de exibição</label>
                            <input type="text" id="displayName" name="display_name" placeholder="Como você quer ser chamado" maxlength="50">
                            <div class="field-help">
                                Nome que aparecerá publicamente (opcional)
                            </div>
                        </div>
                        
                        <div class="form-group full-width">
                            <label for="bio">Biografia</label>
                            <textarea id="bio" name="bio" placeholder="Conte um pouco sobre você..." maxlength="500" rows="4"></textarea>
                            <div class="field-info">
                                <span class="char-counter">0/500</span>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="website">Website</label>
                            <input type="url" id="website" name="website" placeholder="https://seusite.com">
                        </div>
                        
                        <div class="form-group">
                            <label for="location">Localização</label>
                            <input type="text" id="location" name="location" placeholder="Sua cidade, estado">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contact & Social Media Section -->
            <div class="settings-section social-section">
                <div class="section-header">
                    <h2>
                        <i class="fa fa-share-alt"></i>
                        Redes Sociais & Contato
                    </h2>
                    <span class="section-status optional">
                        <i class="fa fa-info-circle"></i>
                        Opcional
                    </span>
                </div>
                
                <div class="section-content">
                    <div class="social-grid">
                        <div class="social-item">
                            <div class="social-icon discord">
                                <i class="fab fa-discord"></i>
                            </div>
                            <div class="social-input">
                                <label for="discord">Discord</label>
                                <input type="text" id="discord" name="discord" placeholder="seuuser#1234">
                            </div>
                        </div>
                        
                        <div class="social-item">
                            <div class="social-icon twitter">
                                <i class="fab fa-twitter"></i>
                            </div>
                            <div class="social-input">
                                <label for="twitter">Twitter/X</label>
                                <input type="text" id="twitter" name="twitter" placeholder="@seuuser">
                            </div>
                        </div>
                        
                        <div class="social-item">
                            <div class="social-icon instagram">
                                <i class="fab fa-instagram"></i>
                            </div>
                            <div class="social-input">
                                <label for="instagram">Instagram</label>
                                <input type="text" id="instagram" name="instagram" placeholder="@seuuser">
                            </div>
                        </div>
                        
                        <div class="social-item">
                            <div class="social-icon youtube">
                                <i class="fab fa-youtube"></i>
                            </div>
                            <div class="social-input">
                                <label for="youtube">YouTube</label>
                                <input type="text" id="youtube" name="youtube" placeholder="Seu canal">
                            </div>
                        </div>
                        
                        <div class="social-item">
                            <div class="social-icon twitch">
                                <i class="fab fa-twitch"></i>
                            </div>
                            <div class="social-input">
                                <label for="twitch">Twitch</label>
                                <input type="text" id="twitch" name="twitch" placeholder="seucanal">
                            </div>
                        </div>
                        
                        <div class="social-item">
                            <div class="social-icon steam">
                                <i class="fab fa-steam"></i>
                            </div>
                            <div class="social-input">
                                <label for="steam">Steam</label>
                                <input type="text" id="steam" name="steam" placeholder="ID do Steam">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

          

            <!-- Privacy & Preferences Section -->
            <div class="settings-section privacy-section">
                <div class="section-header">
                    <h2>
                        <i class="fa fa-shield-alt"></i>
                        Privacidade & Preferências
                    </h2>
                    <span class="section-status required">
                        <i class="fa fa-exclamation-triangle"></i>
                        Importante
                    </span>
                </div>
                
                <div class="section-content">
                    <div class="privacy-grid">
                        <div class="privacy-group">
                            <h3>Visibilidade do Perfil</h3>
                            <div class="privacy-options">
                                <label class="privacy-option">
                                    <input type="radio" name="profile_visibility" value="public" checked>
                                    <div class="option-content">
                                        <div class="option-icon public">
                                            <i class="fa fa-globe"></i>
                                        </div>
                                        <div class="option-info">
                                            <div class="option-title">Público</div>
                                            <div class="option-desc">Qualquer pessoa pode ver seu perfil</div>
                                        </div>
                                    </div>
                                </label>
                                
                                <label class="privacy-option">
                                    <input type="radio" name="profile_visibility" value="members">
                                    <div class="option-content">
                                        <div class="option-icon members">
                                            <i class="fa fa-users"></i>
                                        </div>
                                        <div class="option-info">
                                            <div class="option-title">Apenas membros</div>
                                            <div class="option-desc">Apenas usuários logados podem ver</div>
                                        </div>
                                    </div>
                                </label>
                                
                                <label class="privacy-option">
                                    <input type="radio" name="profile_visibility" value="private">
                                    <div class="option-content">
                                        <div class="option-icon private">
                                            <i class="fa fa-lock"></i>
                                        </div>
                                        <div class="option-info">
                                            <div class="option-title">Privado</div>
                                            <div class="option-desc">Apenas você pode ver seu perfil</div>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        </div>
                        
                      
                        
                        <div class="privacy-group">
                            <h3>Preferências de Conteúdo</h3>
                            <div class="content-preferences">
                                <div class="setting-toggle">
                                    <label class="toggle-label">
                                        <input type="checkbox" name="content[spoilers]" checked>
                                        <div class="toggle-switch"></div>
                                        <div class="toggle-info">
                                            <div class="toggle-title">Mostrar spoilers</div>
                                            <div class="toggle-desc">Exibir conteúdo marcado como spoiler</div>
                                        </div>
                                    </label>
                                </div>
                                
                                
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Account Security Section -->
            <div class="settings-section security-section">
                <div class="section-header">
                    <h2>
                        <i class="fa fa-key"></i>
                        Segurança da Conta
                    </h2>
                    <span class="section-status important">
                        <i class="fa fa-shield-alt"></i>
                        Segurança
                    </span>
                </div>
                
                <div class="section-content">
                    <div class="security-grid">
                        <div class="security-item">
                            <div class="security-icon">
                                <i class="fa fa-envelope"></i>
                            </div>
                            <div class="security-info">
                                <h4>Email</h4>
                                <p>user@email.com</p>
                                <small>Verificado</small>
                            </div>
                            <button type="button" class="btn btn-outline btn-small">Alterar</button>
                        </div>
                        
                        <div class="security-item">
                            <div class="security-icon">
                                <i class="fa fa-lock"></i>
                            </div>
                            <div class="security-info">
                                <h4>Senha</h4>
                                <p>••••••••••</p>
                                <small>Última alteração: há 2 meses</small>
                            </div>
                            <button type="button" class="btn btn-outline btn-small">Alterar</button>
                        </div>
                        
                       
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="form-actions">
                <div class="actions-left">
                    <button type="button" class="btn btn-outline" id="cancelChanges">
                        <i class="fa fa-times"></i>
                        Cancelar
                    </button>
                </div>
                
                <div class="actions-right">
                    <button type="submit" class="btn btn-primary" id="saveProfile">
                        <i class="fa fa-check"></i>
                        Salvar Alterações
                    </button>
                </div>
            </div>
        </form>
    </div>

 
</main>

<!-- Profile Preview Modal -->
<div class="preview-modal" id="profilePreviewModal" style="display: none;">
    <div class="modal-overlay"></div>
    <div class="modal-container">
        <div class="modal-header">
            <h3>
                <i class="fa fa-eye"></i>
                Preview do Perfil
            </h3>
            <button class="modal-close" id="closeProfilePreview">
                <i class="fa fa-times"></i>
            </button>
        </div>
        <div class="modal-content">
            <div class="preview-profile">
                <div class="preview-header">
                    <div class="preview-avatar">
                        <img src="" alt="Avatar Preview" id="previewAvatarImg">
                    </div>
                    <div class="preview-info">
                        <h2 id="previewUsername">Username</h2>
                        <div class="preview-display-name" id="previewDisplayName" style="display: none;"></div>
                        <div class="preview-bio" id="previewBio" style="display: none;"></div>
                        <div class="preview-location" id="previewLocation" style="display: none;">
                            <i class="fa fa-map-marker-alt"></i>
                            <span></span>
                        </div>
                        <div class="preview-website" id="previewWebsite" style="display: none;">
                            <i class="fa fa-link"></i>
                            <a href="#" target="_blank"></a>
                        </div>
                    </div>
                </div>
                <div class="preview-details">
                    <div class="preview-section" id="previewSocial" style="display: none;">
                        <h4>Redes Sociais</h4>
                        <div class="social-links" id="previewSocialLinks"></div>
                    </div>
                    <div class="preview-section" id="previewGaming" style="display: none;">
                        <h4>Gaming</h4>
                        <div class="gaming-info">
                            <div class="platforms-info" id="previewPlatforms" style="display: none;">
                                <strong>Plataformas:</strong> <span></span>
                            </div>
                            <div class="favorite-gta-info" id="previewFavoriteGta" style="display: none;">
                                <strong>GTA Favorito:</strong> <span></span>
                            </div>
                            <div class="gaming-time-info" id="previewGamingTime" style="display: none;">
                                <strong>Tempo de jogo:</strong> <span></span>
                            </div>
                            <div class="favorite-games-info" id="previewFavoriteGames" style="display: none;">
                                <strong>Jogos favoritos:</strong> <span></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" id="closePreviewBtn">Fechar</button>
            <button class="btn btn-primary" id="saveFromPreview">Salvar Perfil</button>
        </div>
    </div>
</div>

<!-- Security Modals -->
<div class="security-modal" id="changeEmailModal" style="display: none;">
    <div class="modal-overlay"></div>
    <div class="modal-container">
        <div class="modal-header">
            <h3>
                <i class="fa fa-envelope"></i>
                Alterar Email
            </h3>
            <button class="modal-close">
                <i class="fa fa-times"></i>
            </button>
        </div>
        <div class="modal-content">
            <form id="changeEmailForm">
                <div class="form-group">
                    <label for="currentEmail">Email atual</label>
                    <input type="email" id="currentEmail" value="user@email.com" disabled>
                </div>
                <div class="form-group">
                    <label for="newEmail">Novo email</label>
                    <input type="email" id="newEmail" placeholder="novo@email.com" required>
                </div>
                <div class="form-group">
                    <label for="confirmPassword">Confirme sua senha</label>
                    <input type="password" id="confirmPassword" placeholder="Digite sua senha atual" required>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" id="cancelEmailChange">Cancelar</button>
            <button class="btn btn-primary" id="confirmEmailChange">Alterar Email</button>
        </div>
    </div>
</div>

<div class="security-modal" id="changePasswordModal" style="display: none;">
    <div class="modal-overlay"></div>
    <div class="modal-container">
        <div class="modal-header">
            <h3>
                <i class="fa fa-lock"></i>
                Alterar Senha
            </h3>
            <button class="modal-close">
                <i class="fa fa-times"></i>
            </button>
        </div>
        <div class="modal-content">
            <form id="changePasswordForm">
                <div class="form-group">
                    <label for="currentPassword">Senha atual</label>
                    <input type="password" id="currentPassword" placeholder="Digite sua senha atual" required>
                </div>
                <div class="form-group">
                    <label for="newPassword">Nova senha</label>
                    <input type="password" id="newPassword" placeholder="Digite a nova senha" required>
                    <div class="password-strength" id="passwordStrength"></div>
                </div>
                <div class="form-group">
                    <label for="confirmNewPassword">Confirmar nova senha</label>
                    <input type="password" id="confirmNewPassword" placeholder="Confirme a nova senha" required>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" id="cancelPasswordChange">Cancelar</button>
            <button class="btn btn-primary" id="confirmPasswordChange">Alterar Senha</button>
        </div>
    </div>
</div>

<div class="security-modal" id="setup2FAModal" style="display: none;">
    <div class="modal-overlay"></div>
    <div class="modal-container">
        <div class="modal-header">
            <h3>
                <i class="fa fa-mobile-alt"></i>
                Configurar Autenticação 2FA
            </h3>
            <button class="modal-close">
                <i class="fa fa-times"></i>
            </button>
        </div>
        <div class="modal-content">
            <div class="twofa-setup">
                <div class="setup-step active" id="step1">
                    <h4>Passo 1: Baixe um app autenticador</h4>
                    <p>Recomendamos o Google Authenticator ou Authy</p>
                    <div class="app-links">
                        <a href="#" class="app-link">
                            <i class="fab fa-google-play"></i>
                            Google Play
                        </a>
                        <a href="#" class="app-link">
                            <i class="fab fa-app-store"></i>
                            App Store
                        </a>
                    </div>
                </div>
                <div class="setup-step" id="step2">
                    <h4>Passo 2: Escaneie o QR Code</h4>
                    <div class="qr-code">
                        <div class="qr-placeholder">
                            <i class="fa fa-qrcode"></i>
                            <p>QR Code aparecerá aqui</p>
                        </div>
                    </div>
                    <div class="manual-entry">
                        <p>Ou digite manualmente:</p>
                        <code>JBSWY3DPEHPK3PXP</code>
                    </div>
                </div>
                <div class="setup-step" id="step3">
                    <h4>Passo 3: Digite o código de verificação</h4>
                    <div class="verification-input">
                        <input type="text" id="verificationCode" placeholder="000000" maxlength="6">
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" id="cancel2FA">Cancelar</button>
            <button class="btn btn-secondary" id="prev2FA" style="display: none;">Anterior</button>
            <button class="btn btn-primary" id="next2FA">Próximo</button>
        </div>
    </div>
</div>

<!-- Notification Container -->
<div id="notification-container"></div>

