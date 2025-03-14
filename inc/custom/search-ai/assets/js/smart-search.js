(function() {
  'use strict';

  const TechSphere = ({ isListening }) => {
    const [audioData, setAudioData] = React.useState(new Uint8Array(128));
    const audioContextRef = React.useRef(null);
    const analyserRef = React.useRef(null);
    const sourceRef = React.useRef(null);
    const animationFrameRef = React.useRef(null);
    const pulseAnimationRef = React.useRef(null);

    const animate = () => {
      if (!isListening) {
        const time = Date.now() * 0.001;
        setAudioData(new Uint8Array(128).map(() => Math.sin(time) * 50 + 50));
        pulseAnimationRef.current = requestAnimationFrame(animate);
      }
    };

    React.useEffect(() => {
      if (!isListening) {
        animate();
      }
      return () => {
        if (pulseAnimationRef.current) {
          cancelAnimationFrame(pulseAnimationRef.current);
        }
      };
    }, [isListening]);

    const startAudioAnalysis = async () => {
      try {
        if (audioContextRef.current) {
          await audioContextRef.current.close();
        }
        const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
        audioContextRef.current = new (window.AudioContext || window.webkitAudioContext)();
        analyserRef.current = audioContextRef.current.createAnalyser();
        sourceRef.current = audioContextRef.current.createMediaStreamSource(stream);
        
        analyserRef.current.fftSize = 256;
        sourceRef.current.connect(analyserRef.current);
        
        updateAnalysis();
      } catch (err) {
        console.error('Erro ao acessar microfone:', err);
      }
    };

    const stopAudioAnalysis = () => {
      if (animationFrameRef.current) {
        cancelAnimationFrame(animationFrameRef.current);
      }
    };

    const updateAnalysis = () => {
      if (!analyserRef.current) return;
      const dataArray = new Uint8Array(analyserRef.current.frequencyBinCount);
      analyserRef.current.getByteFrequencyData(dataArray);
      setAudioData(dataArray);
      animationFrameRef.current = requestAnimationFrame(updateAnalysis);
    };

    React.useEffect(() => {
      if (isListening) {
        startAudioAnalysis();
      } else {
        stopAudioAnalysis();
      }
    }, [isListening]);

    const createWavePath = (radius, offset = 0, amplitude = 1) => {
      const points = 180;
      const centerX = 256;
      const centerY = 256;
      const path = [];
      
      for (let i = 0; i <= points; i++) {
        const angle = (i / points) * 2 * Math.PI;
        const timeOffset = Date.now() * 0.001 + offset;
        
        let waveAmplitude = (audioData[i % audioData.length] || 0) * 0.3 * amplitude;
        const r = radius + waveAmplitude;
        const x = centerX + Math.cos(angle) * r;
        const y = centerY + Math.sin(angle) * r;
        
        if (i === 0) {
          path.push(`M ${x} ${y}`);
        } else {
          path.push(`L ${x} ${y}`);
        }
      }
      
      return path.join(' ') + ' Z';
    };

    return React.createElement('div', { className: 'w-[250px] h-[250px] mx-auto mb-8' },
      React.createElement('svg', {
        viewBox: '0 0 512 512',
        className: 'w-full h-full'
      }, [
        React.createElement('defs', {}, [
          React.createElement('filter', { id: 'neonGlow' }, [
            React.createElement('feGaussianBlur', { stdDeviation: '4', result: 'coloredBlur' }),
            React.createElement('feMerge', {}, [
              React.createElement('feMergeNode', { in: 'coloredBlur' }),
              React.createElement('feMergeNode', { in: 'SourceGraphic' })
            ])
          ])
        ]),
        React.createElement('g', null, [
          React.createElement('path', {
            d: createWavePath(180),
            fill: 'none',
            stroke: '#60a5fa',
            strokeWidth: '4',
            opacity: '0.8',
            filter: 'url(#neonGlow)'
          }),
          React.createElement('path', {
            d: createWavePath(150),
            fill: 'none',
            stroke: '#3b82f6',
            strokeWidth: '4',
            opacity: '0.8',
            filter: 'url(#neonGlow)'
          }),
          React.createElement('circle', {
            cx: '256',
            cy: '256',
            r: '60',
            fill: isListening ? '#ef4444' : '#3b82f6',
            className: 'transition-all duration-300'
          })
        ])
      ])
    );
  };

  const ChatMessages = ({ messages }) => {
    return React.createElement('div', {
      className: 'flex flex-col gap-4 mb-8 chat'
    }, messages.map((msg, index) => 
      React.createElement('div', {
        key: index,
        className: `p-4 rounded-lg ${
          msg.type === 'system' 
            ? 'bg-blue-50 text-blue-900 ia' 
            : 'bg-gray-100 text-gray-900 ml-8 user'
        }`
      }, [
        React.createElement('div', {
          className: 'flex items-start gap-3'
        }, [
          msg.type === 'system' && React.createElement('div', {
            className: 'w-8 h-8 rounded-full bg-blue-500 flex items-center justify-center text-white flex-shrink-0'
          }, ''),
          React.createElement('div', {
            className: 'flex-1'
          }, msg.text)
        ])
      ])
    ));
  };

  const TypewriterMessage = () => {
    const [text, setText] = React.useState('');
    const [isTyping, setIsTyping] = React.useState(true);
    const [isVisible, setIsVisible] = React.useState(false);
    const messageRef = React.useRef(null);
    const fullText = 'Estes foram os resultados mais compatíveis com a sua busca. Deseja realizar uma nova pesquisa?';
    
    React.useEffect(() => {
      const observer = new IntersectionObserver(
        ([entry]) => {
          if (entry.isIntersecting) {
            setIsVisible(true);
          }
        },
        { threshold: 0.5 }
      );
  
      if (messageRef.current) {
        observer.observe(messageRef.current);
      }
  
      return () => {
        if (messageRef.current) {
          observer.unobserve(messageRef.current);
        }
      };
    }, []);
  
    React.useEffect(() => {
      if (!isVisible) return;
  
      let index = 0;
      const timer = setInterval(() => {
        if (index < fullText.length) {
          setText((prev) => prev + fullText.charAt(index));
          index++;
        } else {
          setIsTyping(false);
          clearInterval(timer);
        }
      }, 20);
      
      return () => clearInterval(timer);
    }, [isVisible]);
  
    return React.createElement('div', {
      ref: messageRef,
      className: 'bg-blue-50 text-blue-900 p-4 rounded-lg mt-8 mb-4 cta-ia'
    }, [
      React.createElement('div', {
        className: 'flex items-start gap-3'
      }, [
        React.createElement('div', {
          className: 'w-8 h-8 rounded-full bg-blue-500 flex items-center justify-center text-white flex-shrink-0'
        }, ''),
        React.createElement('div', {
          className: 'flex-1'
        }, [
          React.createElement('p', { className: 'mb-4' }, text),
          !isTyping && React.createElement('button', {
            onClick: () => window.location.reload(),
            className: 'px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors'
          }, 'Nova Pesquisa')
        ])
      ])
    ]);
  };

  const validateSearch = (query, previousQueries) => {
    const allTerms = [...previousQueries, query].join(' ').toLowerCase();
    const required = {
      location: false,
      type: false,
      transaction: false
    };

    const locations = ['asa norte', 'asa sul', 'noroeste', 'sudoeste', 'octogonal', 'cruzeiro', 'lago norte', 'lago sul', 'vicente pires', 'águas claras', 'taguatinga', 'guará', 'ceilândia', 'samambaia', 'recanto das emas', 'riacho fundo', 'riacho fundo ii', 'núcleo bandeirante', 'candangolândia', 'park way', 'parkway', 'brasília', 'paranoá', 'itapoã', 'varjão', 'sobradinho', 'sobradinho ii', 'planaltina', 'santa maria', 'gama', 'brazlândia', 'estrutural', 'jardim botânico', 'são sebastião', 'fercal', 'sol nascente', 'pôr do sol'];
    required.location = locations.some(loc => allTerms.includes(loc));

    const types = ['casa', 'apartamento', 'terreno', 'lote', 'sobrado'];
    required.type = types.some(type => allTerms.includes(type));

    const transactions = ['compra', 'comprar', 'venda', 'vender', 'aluguel', 'alugar'];
    required.transaction = transactions.some(trans => allTerms.includes(trans));

    return {
      isValid: Object.values(required).every(v => v),
      missing: Object.entries(required)
        .filter(([_, value]) => !value)
        .map(([key]) => key)
    };
  };

  function SmartSearch() {
    const [searchQuery, setSearchQuery] = React.useState('');
    const [searchQueries, setSearchQueries] = React.useState([]);
    const [searchResults, setSearchResults] = React.useState([]);
    const [isLoading, setIsLoading] = React.useState(false);
    const [isListening, setIsListening] = React.useState(false);
    const [error, setError] = React.useState(null);
    const [messages, setMessages] = React.useState([
      {
        type: 'system',
        text: `Descreva seu imóvel ideal! Exemplo: comprar casa no Lago Sul com 4 suítes e revestimento travertino romano. Receba opções personalizadas e surpreenda-se!`
      }
    ]);
    const microphoneRef = React.useRef(null);

    const handleSearch = async (e) => {
      e.preventDefault();
      if (!searchQuery.trim()) return;
  
      const newQueries = [...searchQueries, searchQuery];
      const validation = validateSearch(searchQuery, searchQueries);
      
      setMessages(prev => [...prev, { 
          type: 'user', 
          text: searchQuery 
      }]);
  
      if (!validation.isValid) {
          const missingTerms = {
              location: 'a localização',
              type: 'o tipo do imóvel',
              transaction: 'se deseja comprar ou alugar'
          };
  
          const missingText = validation.missing
              .map(term => missingTerms[term])
              .join(', ');
  
          setMessages(prev => [...prev, {
              type: 'system',
              text: `Por favor, informe ${missingText} para que eu possa encontrar as melhores opções para você.`
          }]);
          
          setSearchQueries(newQueries);
          setSearchQuery('');
          return;
      }
  
      setIsLoading(true);
      try {
        const completeQuery = newQueries.join(' ');
        const response = await fetch(
            '/wp-json/smart-search/v1/search?search=' + encodeURIComponent(completeQuery),
            {
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': smartSearchData.nonce,
                    'Accept': 'application/json'
                }
            }
        );
  
          if (!response.ok) {
              throw new Error(`Erro na busca: ${response.status}`);
          }
          
          const results = await response.json();
          
          if (results && results.length > 0) {
              setSearchResults(results);
              setMessages(prev => [...prev, {
                  type: 'system',
                  text: 'Encontrei algumas opções que podem te interessar:'
              }]);
          } else {
              setMessages(prev => [...prev, {
                  type: 'system',
                  text: 'Não encontrei imóveis que correspondam exatamente aos seus critérios. Tente ajustar sua busca.'
              }]);
          }
          
      } catch (error) {
          console.error('Erro detalhado:', error);
          setError('Ocorreu um erro durante a busca. Por favor, tente novamente.');
      } finally {
          setIsLoading(false);
          setSearchQuery('');
      }
  };

    const setupVoiceRecognition = () => {
      if ('webkitSpeechRecognition' in window) {
        const recognition = new window.webkitSpeechRecognition();
        recognition.continuous = true;
        recognition.interimResults = true;
        recognition.lang = 'pt-BR';

        recognition.onstart = () => setIsListening(true);
        
        recognition.onerror = (event) => {
          console.error('Erro no reconhecimento:', event.error);
          setIsListening(false);
        };
        
        recognition.onend = () => {
          setIsListening(false);
        };
        
        recognition.onresult = (event) => {
          const transcript = Array.from(event.results)
            .map(result => result[0].transcript)
            .join('');
          
          setSearchQuery(transcript);

          if (event.results[event.results.length - 1].isFinal) {
            setSearchQuery(transcript);
            stopListening();
            handleSearch(new Event('submit'));
          }
        };

        return recognition;
      }
      return null;
    };

    const startListening = () => {
      const recognition = setupVoiceRecognition();
      if (recognition) {
        microphoneRef.current = recognition;
        recognition.start();
      } else {
        alert('Seu navegador não suporta reconhecimento de voz.');
      }
    };

    const stopListening = () => {
      if (microphoneRef.current) {
        microphoneRef.current.stop();
        setIsListening(false);
      }
    };

    const formatCurrency = (value) => {
      return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL'
      }).format(value);
    };

    const generateCompatibility = (index, total) => {
      const maxPercentage = 98;
      const minPercentage = 61;
      const range = maxPercentage - minPercentage;
      const basePercentage = maxPercentage - ((index / (total - 1)) * range);
      const fluctuation = (Math.random() * 4) - 2;
      return Math.round(Math.max(minPercentage, Math.min(maxPercentage, basePercentage + fluctuation)));
    };

    const PropertyCard = ({ property, isSponsored, index, totalItems }) => {
      const compatibility = !isSponsored ? generateCompatibility(index, totalItems) : null;

      return React.createElement('div', {
        className: `flex gap-4 p-4 rounded-lg border transition-shadow result_item ${
          isSponsored 
            ? 'hover:shadow-lg is_sponsored' 
            : 'border-gray-200 hover:shadow-lg'
        }`
      }, [
        React.createElement('div', { 
          className: 'w-48 h-36 flex-shrink-0 relative overflow-hidden rounded-lg img-wrapper'
        }, 
          React.createElement('img', {
            src: property.thumbnail || '/wp-content/uploads/2025/02/no-image.png',
            alt: property.title,
            className: 'w-full h-full object-cover'
          })
        ),
        React.createElement('div', { className: 'flex-1 text-wrapper' }, [
          React.createElement('div', { 
            className: 'flex justify-between items-start mb-2'
          }, [
            React.createElement('h3', {
              className: 'text-xl font-semibold'
            }, property.title),
            !isSponsored && React.createElement('span', {
              className: 'px-3 py-1 bg-green-100 text-green-800 text-sm rounded-full compatibility-badge'
            }, `${compatibility}% de chance de ser sua casa ideal`),
            isSponsored && React.createElement('span', {
              className: 'px-2 py-1 bg-blue-500 text-white text-sm rounded-full destaque'
            }, 'DESTAQUE')
          ]),
          React.createElement('div', {
            className: 'grid grid-cols-2 gap-2 mb-4'
          }, [
            React.createElement('div', null, `Localização: ${property.location || 'Não informado'}`),
            React.createElement('div', null, `Quartos: ${property.bedrooms || 'Não informado'}`),
            React.createElement('div', null, `Área: ${property.size ? `${property.size}m²` : 'Não informado'}`),
            React.createElement('div', null, `Valor: ${property.amount ? formatCurrency(property.amount) : 'Sob consulta'}`)
          ]),
          React.createElement('div', {
            className: 'flex justify-between items-center'
          }, [
            React.createElement('span', {
              className: 'text-lg font-semibold text-blue-600 super-hidden'
            }, property.amount ? formatCurrency(property.amount) : 'Valor sob consulta'),
            React.createElement('a', {
              href: property.permalink,
              className: 'px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors ver-detalhes'
            }, 'Ver detalhes')
          ])
        ])
      ]);
    };

    return React.createElement('div', { className: 'w-full max-w-4xl mx-auto p-4' }, [
      React.createElement('div', { className: 'flex flex-col items-center chat-container' }, [
        React.createElement(TechSphere, { isListening: isListening }),
        React.createElement(ChatMessages, { messages: messages }),
        React.createElement('form', { 
          onSubmit: handleSearch,
          className: 'w-full mb-8 waves-bg'
        }, [
          React.createElement('div', { className: 'flex gap-2' }, [
            React.createElement('div', { className: 'relative flex-1' }, [
              React.createElement('input', {
                type: 'text',
                value: searchQuery,
                onChange: (e) => setSearchQuery(e.target.value),
                placeholder: 'Descreva o imóvel que procura...',
                className: 'w-full p-4 pr-12 rounded-lg border border-gray-300 focus:outline-none focus:border-blue-500 input-pesquisa'
              }),
              React.createElement('button', {
                type: 'submit',
                className: 'absolute right-4 top-1/2 -translate-y-1/2'
              }, 
                React.createElement('svg', {
                  className: 'w-5 h-5 text-gray-500',
                  fill: 'none',
                  stroke: 'currentColor',
                  viewBox: '0 0 24 24'
                }, [
                  React.createElement('path', {
                    strokeLinecap: 'round',
                    strokeLinejoin: 'round',
                    strokeWidth: '2',
                    d: 'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z'
                  })
                ])
              )
            ]),
            React.createElement('div', { className: 'flex gap-2 botoes' }, [
              React.createElement('button', {
                type: 'button',
                onClick: isListening ? stopListening : startListening,
                className: `p-4 rounded-lg ${isListening ? 'bg-red-500 hover:bg-red-600' : 'bg-blue-500 hover:bg-blue-600'} text-white transition-colors btn_gravar`
              }, isListening ? 'Parar' : 'Gravar'),
              React.createElement('button', {
                type: 'submit',
                className: 'p-4 rounded-lg bg-blue-500 hover:bg-blue-600 text-white transition-colors'
              }, 'Buscar')
            ])
          ])
        ])
      ]),

      error && React.createElement('div', {
        className: 'mb-4 p-4 bg-red-50 text-red-700 rounded-lg'
      }, error),

      isLoading ? 
      React.createElement('div', {
        className: 'text-center py-8'
      }, 'Buscando...') :
      React.createElement('div', { className: 'grid gap-6' }, [
        searchResults
          .filter(property => property.is_sponsored)
          .map((property, index) => 
            React.createElement(PropertyCard, {
              key: property.id,
              property: property,
              isSponsored: true,
              index: index,
              totalItems: searchResults.length
            })
          ),
        React.createElement('div', { className: 'general_items' },
          searchResults
            .filter(property => !property.is_sponsored)
            .map((property, index, filteredArray) => 
              React.createElement(PropertyCard, {
                key: property.id,
                property: property,
                isSponsored: false,
                index: index,
                totalItems: filteredArray.length
              })
            )
        ),
        searchResults.length > 0 && React.createElement(TypewriterMessage)
      ])
    ]);
  }

  document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('smart-search-root');
    if (container) {
      ReactDOM.render(React.createElement(SmartSearch), container);
    }
  });
})();