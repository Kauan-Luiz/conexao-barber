<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Player - WebLab Signage</title>
    <style>
        /* ==========================================
           RESET E TELA CHEIA
           ========================================== */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            background-color: #000; /* Fundo sempre preto pra disfarçar transições */
            overflow: hidden; /* Remove barra de rolagem */
            color: #fff; 
            font-family: 'Segoe UI', sans-serif; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            height: 100vh; 
            width: 100vw;
        }

        /* ==========================================
           TELA DE PAREAMENTO (Flexbox)
           ========================================== */
        #login-screen { 
            display: flex; flex-direction: column; align-items: center; 
            background: #0f172a; padding: 50px; border-radius: 16px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.8); z-index: 100; 
        }
        #login-screen h1 { margin-bottom: 10px; font-size: 28px; color: #3b82f6; }
        #login-screen p { margin-bottom: 30px; color: #94a3b8; font-size: 16px; }
        #tv-token { 
            font-size: 40px; padding: 15px; text-align: center; width: 250px; 
            text-transform: uppercase; border-radius: 8px; border: 2px solid #3b82f6; 
            outline: none; font-family: monospace; letter-spacing: 8px; 
            margin-bottom: 25px; background: #1e293b; color: #fff;
        }
        #tv-token:focus { box-shadow: 0 0 15px rgba(59, 130, 246, 0.5); }
        .btn-conectar { 
            background: #2563eb; color: #fff; border: none; padding: 18px 40px; 
            font-size: 20px; border-radius: 8px; cursor: pointer; font-weight: bold; 
            transition: 0.3s; width: 100%;
        }
        .btn-conectar:hover { background: #1d4ed8; transform: scale(1.02); }

        /* ==========================================
           CONTAINER DE MÍDIA E TRANSIÇÕES
           ========================================== */
        #loading { position: absolute; z-index: 50; font-size: 24px; color: #64748b; display: none; }
        
        #media-container { width: 100%; height: 100%; position: relative; display: none; }
        
        /* A Mágica do CSS: Tudo fica invisível (opacity 0) por padrão */
        .media-element { 
            position: absolute; top: 0; left: 0; width: 100%; height: 100%; 
            object-fit: cover; /* Faz a imagem/vídeo preencher a tela sem esticar feio */
            opacity: 0; 
            transition: opacity 1s ease-in-out; /* Transição suave de 1 segundo */
        }
        
        /* Quando o JS colocar a classe 'active', a mídia aparece suavemente */
        .media-element.active { opacity: 1; z-index: 10; }

        /* Status invisível no canto para debug */
        #tv-status { position: absolute; bottom: 10px; right: 10px; font-size: 12px; color: rgba(255,255,255,0.3); z-index: 999; }
    </style>
</head>
<body>

    <div id="login-screen">
        <h1>WebLab Signage</h1>
        <p>Digite o código de pareamento desta TV</p>
        <input type="text" id="tv-token" maxlength="6" placeholder="000000" autocomplete="off">
        <button class="btn-conectar" onclick="iniciarPlayer()">Conectar Aparelho</button>
    </div>

    <div id="loading">Sincronizando Roteiro...</div>

    <div id="media-container">
        <img id="img-player" class="media-element" src="">
        <video id="video-player" class="media-element" muted></video>
    </div>

    <div id="tv-status"></div>

    <script>
        // ==========================================
        // O MOTOR JAVASCRIPT DO PLAYER (CORRIGIDO)
        // ==========================================
        const loginScreen = document.getElementById('login-screen');
        const mediaContainer = document.getElementById('media-container');
        const imgPlayer = document.getElementById('img-player');
        const videoPlayer = document.getElementById('video-player');
        const loading = document.getElementById('loading');
        const tvStatus = document.getElementById('tv-status');
        
        let playlist = [];
        let currentIndex = 0;
        let timerLoop = null; 
        let isPlaying = false; // FLAG NOVA: Pra saber se a TV já está rodando
        
        // 1. Tenta pegar o token salvo
        let token = localStorage.getItem('weblab_tv_token') || '';

        // 2. Tenta pegar da URL
        const urlParams = new URLSearchParams(window.location.search);
        if(urlParams.has('token')) {
            token = urlParams.get('token');
        }

        if(token) {
            document.getElementById('tv-token').value = token;
            iniciarPlayer();
        }

        async function iniciarPlayer() {
            token = document.getElementById('tv-token').value.trim().toUpperCase();
            if(token.length !== 6) {
                alert("O código precisa ter exatamente 6 caracteres.");
                return;
            }
            
            localStorage.setItem('weblab_tv_token', token);
            loginScreen.style.display = 'none';
            loading.style.display = 'block';
            
            await buscarAtualizacao();
            
            // Reduzi de 60s pra 30s. Assim se você mudar algo no painel, a TV atualiza mais rápido!
            setInterval(buscarAtualizacao, 30000); 
        }

        async function buscarAtualizacao() {
            try {
                const resposta = await fetch(`api.php?token=${token}`);
                const dados = await resposta.json();
                
                if(!dados.sucesso) {
                    tvStatus.innerText = "Erro: " + dados.erro;
                    return;
                }
                
                tvStatus.innerText = `Conectado: ${dados.tv_nome}`;
                const novaPlaylist = dados.midias;
                
                // Se a playlist mudou lá no painel, atualiza aqui
                if(JSON.stringify(playlist) !== JSON.stringify(novaPlaylist)) {
                    playlist = novaPlaylist;
                    console.log("Roteiro atualizado! Itens:", playlist.length);
                    
                    if(playlist.length > 0) {
                        // Tira o loading e mostra o container de vídeos/fotos
                        loading.style.display = 'none';
                        mediaContainer.style.display = 'block';
                        
                        // Se não estava tocando nada, dá o play!
                        if(!isPlaying) {
                            currentIndex = 0;
                            isPlaying = true;
                            rodarMidia();
                        }
                    } else {
                        // Se limparam a playlist lá no painel, volta pra tela de aguarde
                        mediaContainer.style.display = 'none';
                        loading.style.display = 'block';
                        loading.innerText = "Aguardando roteiro no painel...";
                        isPlaying = false;
                        clearTimeout(timerLoop);
                    }
                }
            } catch(e) {
                console.error("Erro de conexão", e);
            }
        }

        function rodarMidia() {
            if(playlist.length === 0) return;
            
            if(currentIndex >= playlist.length) {
                currentIndex = 0; // Volta pro começo do loop
            }
            
            const midiaAtual = playlist[currentIndex];
            const urlArquivo = `../uploads/${midiaAtual.arquivo}`;
            
            // Apaga o que está na tela
            imgPlayer.classList.remove('active');
            videoPlayer.classList.remove('active');
            
            // Espera o Fade Out (600ms) e joga a próxima mídia
            setTimeout(() => {
                
                if(midiaAtual.tipo === 'imagem') {
                    videoPlayer.pause(); 
                    
                    imgPlayer.src = urlArquivo;
                    imgPlayer.classList.add('active');
                    
                    clearTimeout(timerLoop);
                    timerLoop = setTimeout(() => {
                        currentIndex++;
                        rodarMidia();
                    }, midiaAtual.duracao * 1000); // Segundos convertidos pra ms
                    
                } else if(midiaAtual.tipo === 'video') {
                    videoPlayer.src = urlArquivo;
                    videoPlayer.classList.add('active');
                    videoPlayer.play().catch(e => console.log("Bloqueio de Autoplay", e));
                    
                    // Quando o vídeo acabar, chama a próxima!
                    videoPlayer.onended = () => {
                        currentIndex++;
                        rodarMidia();
                    };
                }
                
            }, 600);
        }
    </script>
</body>
</html>