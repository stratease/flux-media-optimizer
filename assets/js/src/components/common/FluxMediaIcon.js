import React from 'react';

/**
 * Flux Media brand icon component
 * 
 * @since 1.0.0
 */
const FluxMediaIcon = ({ size = 32, ...props }) => {
  return (
    <svg
      xmlns="http://www.w3.org/2000/svg"
      viewBox="0 0 200 200"
      width={size}
      height={size}
      aria-labelledby="title desc"
      role="img"
      {...props}
    >
      <title id="title">Flux Media Logo</title>
      <desc id="desc">Stylized three-armed electrode meeting at a glowing center with metallic strokes and warm glow.</desc>

      <defs>
        {/* metallic stroke gradient */}
        <linearGradient id="metal" x1="0" x2="1">
          <stop offset="0" stopColor="#e6e6e6"/>
          <stop offset="0.35" stopColor="#bfbfbf"/>
          <stop offset="0.65" stopColor="#ffffff"/>
          <stop offset="1" stopColor="#d9d9d9"/>
        </linearGradient>

        {/* inner highlight for arms */}
        <linearGradient id="highlight" x1="0" x2="1">
          <stop offset="0" stopColor="#fff7d9" stopOpacity="0.9"/>
          <stop offset="1" stopColor="#ffd08a" stopOpacity="0.6"/>
        </linearGradient>

        {/* warm glow around center */}
        <radialGradient id="centerGlow" cx="50%" cy="50%" r="50%">
          <stop offset="0" stopColor="#ffd94d" stopOpacity="0.95"/>
          <stop offset="0.4" stopColor="#ffb34d" stopOpacity="0.55"/>
          <stop offset="1" stopColor="#ff8a4d" stopOpacity="0"/>
        </radialGradient>

        {/* blurred halo filter */}
        <filter id="halo" x="-50%" y="-50%" width="200%" height="200%">
          <feGaussianBlur stdDeviation="6" result="blur"/>
          <feMerge>
            <feMergeNode in="blur"/>
            <feMergeNode in="SourceGraphic"/>
          </feMerge>
        </filter>

        {/* subtle drop shadow */}
        <filter id="shadow" x="-20%" y="-20%" width="140%" height="140%">
          <feOffset dy="2" in="SourceAlpha" result="off"/>
          <feGaussianBlur in="off" stdDeviation="2" result="blur"/>
          <feColorMatrix in="blur" type="matrix"
            values="0 0 0 0 0
                    0 0 0 0 0
                    0 0 0 0 0
                    0 0 0 0.18" result="shadow"/>
          <feMerge>
            <feMergeNode in="shadow"/>
            <feMergeNode in="SourceGraphic"/>
          </feMerge>
        </filter>
      </defs>

      {/* background circle faint */}
      <circle cx="100" cy="100" r="96" fill="transparent"/>

      {/* glowing center */}
      <circle cx="100" cy="100" r="18" fill="url(#centerGlow)" filter="url(#halo)"/>

      {/* three metallic arms (rounded) */}

      {/* Left arm */}
      <g strokeLinecap="round" strokeLinejoin="round" filter="url(#shadow)">
        {/* outer stroke for metallic */}
        <path d="M58 144 L58 110 C58 98 70 90 82 90 C94 90 106 98 106 110 L106 124"
              fill="none" stroke="url(#metal)" strokeWidth="10"/>
        {/* inner highlight */}
        <path d="M62 140 L62 112 C62 100 72 94 82 94 C92 94 102 100 102 112 L102 122"
              fill="none" stroke="url(#highlight)" strokeWidth="4"/>
      </g>

      {/* Right arm (mirror) */}
      <g strokeLinecap="round" strokeLinejoin="round" filter="url(#shadow)">
        <path d="M142 144 L142 110 C142 98 130 90 118 90 C106 90 94 98 94 110 L94 124"
              fill="none" stroke="url(#metal)" strokeWidth="10"/>
        <path d="M138 140 L138 112 C138 100 128 94 118 94 C108 94 98 100 98 112 L98 122"
              fill="none" stroke="url(#highlight)" strokeWidth="4"/>
      </g>

      {/* Top arm */}
      <g strokeLinecap="round" strokeLinejoin="round" filter="url(#shadow)">
        <path d="M84 54 L100 74 L116 54 L116 74 C116 86 106 96 94 96 C82 96 74 86 74 74 L74 60"
              fill="none" stroke="url(#metal)" strokeWidth="10"/>
        <path d="M88 58 L100 72 L112 58 L112 72 C112 84 104 92 94 92 C84 92 76 84 76 72 L76 64"
              fill="none" stroke="url(#highlight)" strokeWidth="4"/>
      </g>

      {/* subtle connecting beams into center */}
      <g strokeLinecap="round" strokeLinejoin="round">
        <path d="M106 124 C106 118 103 112 100 110" fill="none" stroke="#ffb34d" strokeWidth="3" strokeOpacity="0.85"/>
        <path d="M94 124 C94 118 97 112 100 110" fill="none" stroke="#ffb34d" strokeWidth="3" strokeOpacity="0.85"/>
        <path d="M98 92 C100 90 102 90 106 74" fill="none" stroke="#ffb34d" strokeWidth="3" strokeOpacity="0.85"/>
        <path d="M102 92 C100 90 98 90 94 74" fill="none" stroke="#ffb34d" strokeWidth="3" strokeOpacity="0.85"/>
      </g>

      {/* glossy rim around the center to suggest a connector */}
      <circle cx="100" cy="100" r="9" fill="#fff" opacity="0.9"/>
      <circle cx="100" cy="100" r="6" fill="#ffd94d" opacity="0.95"/>
    </svg>
  );
};

export default FluxMediaIcon;
