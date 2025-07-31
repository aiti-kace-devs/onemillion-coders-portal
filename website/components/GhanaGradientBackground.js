import React from 'react';

const GhanaGradientBackground = ({ 
  size = 'md',
  opacity = '5',
  blur = '3xl',
  shape = 'circular', // circular, full
  direction = 'br', // br (bottom-right), t (top), r (right), etc.
  position = 'center', // center, top-left, top-right, bottom-left, bottom-right, custom
  customPosition = '',
  className = '',
  ...props 
}) => {
  // Size variants
  const sizeVariants = {
    'xs': 'w-32 h-32',
    'sm': 'w-48 h-48',
    'md': 'w-64 h-64',
    'lg': 'w-80 h-80',
    'xl': 'w-96 h-96',
    '2xl': 'w-[32rem] h-[32rem]'
  };

  // Position variants
  const positionVariants = {
    'center': 'top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2',
    'top-left': 'top-10 left-10',
    'top-right': 'top-10 right-10',
    'bottom-left': 'bottom-10 left-10',
    'bottom-right': 'bottom-10 right-10',
    'top-center': 'top-1/4 left-1/2 transform -translate-x-1/2',
    'bottom-center': 'bottom-1/4 left-1/2 transform -translate-x-1/2',
    'custom': customPosition
  };

  // Shape variants
  const shapeVariants = {
    'circular': 'rounded-full',
    'full': ''
  };

  // Direction variants for gradients
  const directionVariants = {
    'br': 'bg-gradient-to-br from-red-600 via-yellow-400 to-green-600',
    't': 'bg-gradient-to-t from-red-600 via-yellow-400 to-green-600',
    'r': 'bg-gradient-to-r from-red-600 via-yellow-400 to-green-600',
    'l': 'bg-gradient-to-l from-red-600 via-yellow-400 to-green-600',
    'b': 'bg-gradient-to-b from-red-600 via-yellow-400 to-green-600'
  };

  // Build the final className
  const finalClasses = [
    'absolute',
    positionVariants[position],
    sizeVariants[size],
    shapeVariants[shape],
    directionVariants[direction],
    `opacity-${opacity}`,
    `blur-${blur}`,
    className
  ].filter(Boolean).join(' ');

  return (
    <div 
      className={finalClasses}
      {...props}
    />
  );
};

export default GhanaGradientBackground; 