'use client';

import { motion } from 'framer-motion';
import { useState } from 'react';

const Button = ({
  children,
  onClick,
  icon: Icon,
  variant = 'primary',
  size = 'medium',
  disabled = false,
  className = '',
  iconPosition = 'right',
  ...props
}) => {
  const [isHovered, setIsHovered] = useState(false);

  // Variant styles
  const variants = {
    primary: {
      base: 'bg-yellow-400 text-gray-900 hover:bg-yellow-500 shadow-md hover:shadow-lg',
      style: { backgroundColor: '#F9A825', color: '#121212' }
    },
    secondary: {
      base: 'bg-white text-gray-900 border border-gray-300 hover:bg-gray-50 shadow-sm hover:shadow-md',
      style: { color: '#121212' }
    },
    success: {
      base: 'bg-green-600 text-white hover:bg-green-700 shadow-md hover:shadow-lg',
      style: { backgroundColor: '#2E7D32' }
    },
    danger: {
      base: 'bg-red-600 text-white hover:bg-red-700 shadow-md hover:shadow-lg',
      style: { backgroundColor: '#C62828' }
    },
    ghost: {
      base: 'bg-transparent text-gray-700 hover:bg-gray-100 border border-transparent hover:border-gray-200',
      style: { color: '#121212' }
    },
    outline: {
      base: 'bg-transparent border-2 border-yellow-400 text-yellow-600 hover:bg-yellow-400 hover:!text-black transition-colors duration-200',
      style: { borderColor: '#F9A825', color: '#F9A825' }
    }
  };

  // Size styles - improved consistency
  const sizes = {
    small: 'px-4 py-2 text-sm',
    medium: 'px-6 py-3 text-sm',
    large: 'px-8 py-4 text-base'
  };

  const currentVariant = variants[variant] || variants.primary;
  const currentSize = sizes[size] || sizes.medium;

  // Icon animation variants - simple hover animation
  const iconVariants = {
    idle: {
      x: 0,
      transition: {
        duration: 0.2,
        ease: 'easeOut'
      }
    },
    hover: {
      x: 3, // Small slide to the right on hover
      transition: {
        duration: 0.2,
        ease: 'easeOut'
      }
    }
  };

  // Button animation variants
  const buttonVariants = {
    idle: {
      scale: 1,
      y: 0
    },
    hover: {
      scale: 1.02,
      y: -1,
      transition: {
        duration: 0.2,
        ease: 'easeOut'
      }
    },
    tap: {
      scale: 0.98,
      y: 0
    }
  };

  return (
    <motion.button
      className={`
        cursor-pointer relative overflow-hidden font-semibold rounded-full 
        transition-all duration-300 cubic-bezier(0.4, 0, 0.2, 1)
        disabled:opacity-50 disabled:cursor-not-allowed
        focus:outline-none focus:ring-2 focus:ring-yellow-400 focus:ring-offset-2
        ${currentVariant.base} ${currentSize} ${className}
      `}
      style={currentVariant.style}
      variants={buttonVariants}
      initial="idle"
      whileHover={!disabled ? "hover" : "idle"}
      whileTap={!disabled ? "tap" : "idle"}
      onHoverStart={() => !disabled && setIsHovered(true)}
      onHoverEnd={() => !disabled && setIsHovered(false)}
      onClick={!disabled ? onClick : undefined}
      disabled={disabled}
      {...props}
    >
      <div className="flex items-center justify-center space-x-2">
        {/* Left Icon */}
        {Icon && iconPosition === 'left' && (
          <motion.div
            variants={iconVariants}
            animate={isHovered ? 'hover' : 'idle'}
            className="flex items-center justify-center"
          >
            <Icon size={16} />
          </motion.div>
        )}

        {/* Button Text */}
        <span className="relative z-10">
          {children}
        </span>

        {/* Right Icon */}
        {Icon && iconPosition === 'right' && (
          <motion.div
            variants={iconVariants}
            animate={isHovered ? 'hover' : 'idle'}
            className="flex items-center justify-center"
          >
            <Icon size={16} />
          </motion.div>
        )}
      </div>


    </motion.button>
  );
};

export default Button; 