import React from 'react';

const GhanaGradientBar = ({ 
  height = '3px', 
  position = 'top', 
  opacity,
  zIndex = 10,
  absolute = true,
  className = '',
  ...props 
}) => {
  // Base gradient classes  
  const baseClasses = 'bg-gradient-to-r from-red-600 via-yellow-400 to-green-600 bg-red-500';
  
  // Position classes
  const positionClasses = absolute ? {
    top: 'absolute top-0 left-0 right-0',
    bottom: 'absolute bottom-0 left-0 right-0',
    'top-full': 'absolute top-0 left-0 w-full',
    'bottom-full': 'absolute bottom-0 left-0 w-full'
  } : {};
  
  // Height mapping for common values
  const heightClasses = {
    '1px': 'h-px',
    '2px': 'h-0.5',
    '3px': 'h-0.5', // Closest to 3px
    '4px': 'h-1',
    '5px': 'h-1',
    '6px': 'h-1.5',
    '8px': 'h-2',
    '10px': 'h-2.5',
    '12px': 'h-3',
    '16px': 'h-4'
  };
  
  // Z-index mapping
  const zIndexClasses = {
    0: 'z-0',
    10: 'z-10',
    20: 'z-20',
    30: 'z-30',
    40: 'z-40',
    50: 'z-50'
  };
  
  // Opacity mapping
  const opacityClasses = opacity ? {
    10: 'opacity-10',
    20: 'opacity-20',
    30: 'opacity-30',
    40: 'opacity-40',
    50: 'opacity-50',
    60: 'opacity-60',
    70: 'opacity-70',
    80: 'opacity-80',
    90: 'opacity-90',
    100: 'opacity-100'
  }[opacity] : '';
  
  // Build the final className
  const finalClasses = [
    'ghana-gradient-bar', // debug class
    baseClasses,
    absolute ? positionClasses[position] : '',
    heightClasses[height] || 'h-0.5', // fallback to 2px equivalent
    absolute && zIndexClasses[zIndex] ? zIndexClasses[zIndex] : '',
    opacityClasses,
    className
  ].filter(Boolean).join(' ');

  // For exact height control when Tailwind classes don't match
  const inlineStyles = !heightClasses[height] ? { height } : {};

  return (
    <div 
      className={finalClasses}
      style={inlineStyles}
      {...props}
    />
  );
};

export default GhanaGradientBar; 