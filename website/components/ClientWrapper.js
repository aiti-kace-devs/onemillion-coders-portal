"use client";

import { useState, useEffect } from "react";
import SplashScreen from "./SplashScreen";

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
    return <>{children}</>;
  }

  return (
    <>
      {showSplash && <SplashScreen onDismiss={handleSplashDismiss} />}
      {children}
    </>
  );
}



const data = {
  "form_uuid": "6c004031-4efb-4b51-890f-0c3788defedf",
  "response_data": {
      "name": "John Doe",
      "email": "john@doe.com",
      "age": "20–24",
      "highest-level-of-education": "Degree",
      "phone": "+233542323133",
      "gender": "Male",
      "course_id": "27"
  }
}
