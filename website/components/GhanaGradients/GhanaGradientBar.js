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
  const baseClasses = 'bg-gradient-to-r from-red-600 via-yellow-400 to-green-600';
  
  // Position classes
  const positionClasses = absolute ? {
    top: 'absolute top-0 left-0 right-0',
    bottom: 'absolute bottom-0 left-0 right-0',
    'top-full': 'absolute top-0 left-0 w-full',
    'bottom-full': 'absolute bottom-0 left-0 w-full'
  } : {};
  
  // Build the final className
  const finalClasses = [
    baseClasses,
    absolute ? positionClasses[position] : '',
    `h-[${height}]`,
    zIndex && absolute ? `z-${zIndex}` : '',
    opacity ? `opacity-${opacity}` : '',
    className
  ].filter(Boolean).join(' ');

  return (
    <div 
      className={finalClasses}
      {...props}
    />
  );
};

export default GhanaGradientBar; 