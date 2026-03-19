"use client";

import { useState, useEffect } from "react";
import SplashScreen from "./SplashScreen";
// import ConsoleBranding from "./ConsoleBranding";
import { GoogleReCaptchaProvider } from 'react-google-recaptcha-v3';

export default function ClientWrapper({ children }) {
  const [showSplash, setShowSplash] = useState(false);
  const [isClient, setIsClient] = useState(false);

  useEffect(() => {
    // localStorage.clear()
    setIsClient(true);

    // Check if user has opted out of seeing the splash screen
    const hasOptedOut = localStorage.getItem("splashScreenOptOut");
    if (hasOptedOut !== "true") {
      setShowSplash(true);
    }
  }, []);

  const handleSplashDismiss = () => {
    setShowSplash(false);
  };

  // Don't render splash screen during SSR
  if (!isClient) {
    return(  <GoogleReCaptchaProvider reCaptchaKey={process.env.NEXT_PUBLIC_RECAPTCHA_SITE_KEY}
      
     useEnterprise={true}
     container={{
        parameters: {
          badge: 'bottomright', // Ensures the script knows where to render
        }
      }}
      scriptProps={{
        async: true,
        defer: true,
        appendTo: 'head',
        nonce: undefined,
      }}>{children}</GoogleReCaptchaProvider>);
  }

  return (
    <GoogleReCaptchaProvider reCaptchaKey={process.env.NEXT_PUBLIC_RECAPTCHA_SITE_KEY}
    useEnterprise={true}
    container={{
        parameters: {
          badge: 'bottomright', // Ensures the script knows where to render
        }
      }}
    scriptProps={{
      async: false,
      defer: false,
      appendTo: 'head',
      nonce: undefined,
    }}>
      {/* <ConsoleBranding /> */}
      {showSplash && <SplashScreen onDismiss={handleSplashDismiss} />}
      {children}
    </GoogleReCaptchaProvider>
  );
}  