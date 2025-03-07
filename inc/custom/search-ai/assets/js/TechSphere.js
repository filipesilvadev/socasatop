import React, { useEffect, useRef, useState } from 'react';

const TechSphere = ({ isListening }) => {
  const [audioData, setAudioData] = useState(new Uint8Array(128));
  const audioContextRef = useRef(null);
  const analyserRef = useRef(null);
  const sourceRef = useRef(null);
  const animationRef = useRef(null);

  useEffect(() => {
    if (isListening) {
      startAudioAnalysis();
    } else {
      stopAudioAnalysis();
    }
    return () => stopAudioAnalysis();
  }, [isListening]);

  const startAudioAnalysis = async () => {
    try {
      const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
      audioContextRef.current = new (window.AudioContext || window.webkitAudioContext)();
      analyserRef.current = audioContextRef.current.createAnalyser();
      sourceRef.current = audioContextRef.current.createMediaStreamSource(stream);
      
      analyserRef.current.fftSize = 256;
      sourceRef.current.connect(analyserRef.current);
      
      updateAnalysis();
    } catch (err) {
      console.error('Error accessing microphone:', err);
    }
  };

  const stopAudioAnalysis = () => {
    if (animationRef.current) {
      cancelAnimationFrame(animationRef.current);
    }
    if (audioContextRef.current) {
      audioContextRef.current.close();
    }
    if (sourceRef.current) {
      sourceRef.current.disconnect();
    }
  };

  const updateAnalysis = () => {
    if (!analyserRef.current) return;
    const dataArray = new Uint8Array(analyserRef.current.frequencyBinCount);
    analyserRef.current.getByteFrequencyData(dataArray);
    setAudioData(dataArray);
    animationRef.current = requestAnimationFrame(updateAnalysis);
  };

  const createWavePath = (radius, offset = 0, amplitude = 1) => {
    const points = 180;
    const centerX = 256;
    const centerY = 256;
    const path = [];
    
    for (let i = 0; i <= points; i++) {
      const angle = (i / points) * 2 * Math.PI;
      const timeOffset = Date.now() * 0.001 + offset;
      
      let waveAmplitude;
      if (isListening) {
        const dataIndex = Math.floor((i / points) * audioData.length);
        waveAmplitude = (audioData[dataIndex] || 0) * 0.3 * amplitude;
      } else {
        waveAmplitude = Math.sin(angle * 8 + timeOffset) * 10 * amplitude;
      }

      const r = radius + waveAmplitude;
      const x = centerX + Math.cos(angle) * r;
      const y = centerY + Math.sin(angle) * r;
      
      if (i === 0) {
        path.push(`M ${x} ${y}`);
      } else {
        const cpRadius = r * 0.552284;
        const prevAngle = ((i - 1) / points) * 2 * Math.PI;
        const cp1x = centerX + Math.cos(prevAngle + Math.PI / 6) * cpRadius;
        const cp1y = centerY + Math.sin(prevAngle + Math.PI / 6) * cpRadius;
        path.push(`S ${cp1x} ${cp1y} ${x} ${y}`);
      }
    }
    
    return path.join(' ') + ' Z';
  };

  return (
    <div className="w-[512px] h-[512px] mx-auto mb-8">
      <svg 
        viewBox="0 0 512 512" 
        className="w-full h-full"
      >
        <defs>
          {/* Gradientes principais */}
          <radialGradient id="sphereGradient" cx="50%" cy="50%" r="50%">
            <stop offset="0%" stopColor={isListening ? '#4f46e5' : '#3b82f6'} stopOpacity="0.2" />
            <stop offset="70%" stopColor={isListening ? '#818cf8' : '#60a5fa'} stopOpacity="0.1" />
            <stop offset="100%" stopColor={isListening ? '#c7d2fe' : '#93c5fd'} stopOpacity="0" />
          </radialGradient>

          <radialGradient id="coreGradient" cx="30%" cy="30%" r="70%">
            <stop offset="0%" stopColor={isListening ? '#4338ca' : '#2563eb'} stopOpacity="0.9" />
            <stop offset="100%" stopColor={isListening ? '#6366f1' : '#3b82f6'} stopOpacity="0.6" />
          </radialGradient>

          {/* Filtros para efeitos de glow */}
          <filter id="glow">
            <feGaussianBlur stdDeviation="3" result="coloredBlur"/>
            <feMerge>
              <feMergeNode in="coloredBlur"/>
              <feMergeNode in="SourceGraphic"/>
            </feMerge>
          </filter>

          <filter id="innerGlow">
            <feGaussianBlur stdDeviation="6" result="blur"/>
            <feComposite in="SourceGraphic" in2="blur" operator="arithmetic" k1="1" k2="-1" k3="1" k4="0"/>
          </filter>
        </defs>

        {/* Camadas de ondas externas */}
        <g className="transition-opacity duration-300">
          <path
            d={createWavePath(180, 0, 1)}
            fill="none"
            stroke="url(#sphereGradient)"
            strokeWidth="1"
            opacity="0.3"
            filter="url(#glow)"
          />
          <path
            d={createWavePath(150, 2, 0.8)}
            fill="none"
            stroke="url(#sphereGradient)"
            strokeWidth="1.5"
            opacity="0.4"
            filter="url(#glow)"
          />
          <path
            d={createWavePath(120, 4, 0.6)}
            fill="none"
            stroke="url(#sphereGradient)"
            strokeWidth="2"
            opacity="0.5"
            filter="url(#glow)"
          />
        </g>

        {/* Núcleo da esfera */}
        <circle
          cx="256"
          cy="256"
          r="80"
          fill="url(#coreGradient)"
          filter="url(#innerGlow)"
          className="transition-all duration-300"
          style={{
            transform: `scale(${isListening ? 1.1 : 1})`,
            transformOrigin: 'center'
          }}
        />

        {/* Ondas internas */}
        <g clipPath="url(#sphereClip)" className="transition-opacity duration-300">
          <path
            d={createWavePath(60, 6, 0.4)}
            fill="none"
            stroke="rgba(255, 255, 255, 0.3)"
            strokeWidth="2"
            opacity="0.6"
          />
          <path
            d={createWavePath(40, 8, 0.3)}
            fill="none"
            stroke="rgba(255, 255, 255, 0.4)"
            strokeWidth="2"
            opacity="0.7"
          />
        </g>

        {/* Clip path para ondas internas */}
        <clipPath id="sphereClip">
          <circle cx="256" cy="256" r="80" />
        </clipPath>
      </svg>
    </div>
  );
};

export default TechSphere;