import { Suspense } from "react";
import { Lato } from "next/font/google";
import "./globals.css";
import LayoutShell from "../components/LayoutShell";
import ClientWrapper from "@/components/ClientWrapper";

const lato = Lato({
  variable: "--font-lato",
  subsets: ["latin"],
  weight: ["100", "300", "400", "700", "900"],
});

export const metadata = {
  title: "One Million Coders Portal",
  description:
    "Join the One Million Coders community and advance your tech career",
  icons: {
    icon: "/images/white-logo.png",
  },
};

export default async function RootLayout({ children }) {
  return (
    <html lang="en" className="bg-white [color-scheme:light]" suppressHydrationWarning>
      <body className={`${lato.variable} antialiased bg-white`} suppressHydrationWarning>
        <ClientWrapper>
          <Suspense>
            <LayoutShell>
              {children}
            </LayoutShell>
          </Suspense>
        </ClientWrapper>
      </body>
    </html>
  );
}
