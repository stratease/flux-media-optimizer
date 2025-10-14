import React from 'react';

/**
 * Flux Media brand icon component
 * 
 * @since 0.1.0
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
      <desc id="desc">Symmetrical triangle logo with energy filaments and electricity effects representing power and flow.</desc>

      <defs>
        {/* Energy gradient for the outer triangle */}
        <linearGradient id="energyGradient" x1="0%" y1="0%" x2="100%" y2="100%">
          <stop offset="0%" style={{stopColor: '#00A3FF', stopOpacity: 1}} />
          <stop offset="100%" style={{stopColor: '#00FFFF', stopOpacity: 1}} />
        </linearGradient>
      </defs>

      {/* Background (transparent) */}
      <rect width="200" height="200" fill="none"/>

      {/* Symmetrical outer triangle: Closed path with curved edges */}
      <path 
        d="M 100 30 
           C 130 50, 170 110, 170 150 
           C 170 170, 30 170, 30 150 
           C 30 110, 70 50, 100 30 Z" 
        fill="none" 
        stroke="url(#energyGradient)" 
        strokeWidth="6" 
        strokeLinecap="round"
      />

      {/* Symmetrical inner Y-shape filaments: Curved paths converging at center */}
      <path 
        d="M 100 30 C 100 50, 100 70, 100 90" 
        stroke="#00A3FF" 
        strokeWidth="4" 
        strokeLinecap="round"
      />
      <path 
        d="M 30 150 C 50 130, 70 110, 100 90" 
        stroke="#00A3FF" 
        strokeWidth="4" 
        strokeLinecap="round"
      />
      <path 
        d="M 170 150 C 150 130, 130 110, 100 90" 
        stroke="#00A3FF" 
        strokeWidth="4" 
        strokeLinecap="round"
      />

      {/* Central node (energy core) */}
      <circle 
        cx="100" 
        cy="90" 
        r="6" 
        fill="#00FFFF"
      />

      {/* Electricity: Jagged solid lines along triangle edges */}
      <path 
        d="M 100 30 C 110 40, 125 60, 130 80 C 135 100, 155 120, 170 150" 
        stroke="#00FFFF" 
        strokeWidth="2" 
        strokeLinecap="round" 
        opacity="0.8"
      />
      <path 
        d="M 170 150 C 155 160, 135 165, 100 170 C 65 165, 45 160, 30 150" 
        stroke="#00FFFF" 
        strokeWidth="2" 
        strokeLinecap="round" 
        opacity="0.8"
      />
      <path 
        d="M 30 150 C 45 120, 65 100, 70 80 C 75 60, 90 40, 100 30" 
        stroke="#00FFFF" 
        strokeWidth="2" 
        strokeLinecap="round" 
        opacity="0.8"
      />
    </svg>
  );
};

export default FluxMediaIcon;
