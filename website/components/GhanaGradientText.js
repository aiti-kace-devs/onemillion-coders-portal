import React from 'react';

const GhanaGradientText = ({ 
  children, 
  className = '',
  variant = 'red-yellow-green', // red-yellow-green, green-yellow-red
  ...props 
}) => {
  // Define gradient variants
  const gradientVariants = {
    'red-yellow-green': 'bg-gradient-to-r from-red-600 via-yellow-400 to-green-600',
    'green-yellow-red': 'bg-gradient-to-r from-green-600 via-yellow-400 to-red-600'
  };

  // Build the final className
  const finalClasses = [
    'text-transparent',
    'bg-clip-text',
    gradientVariants[variant],
    className
  ].filter(Boolean).join(' ');

  return (
    <span 
      className={finalClasses}
      {...props}
    >
      {children}
    </span>
  );
};

export default GhanaGradientText; 