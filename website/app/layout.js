import { Lato } from "next/font/google";
import "./globals.css";
import Header from "../components/Header";
import Footer from "@/components/Footer";
import ClientWrapper from "@/components/ClientWrapper";
import { getFooterData } from "@/services"; 

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
  let footerData = null;
  try {
    footerData = await getFooterData();
  } catch (error) {
    console.error("Failed to fetch footer data:", error);
  }

  return (
    <html lang="en">
      <body className={`${lato.variable} antialiased`}> 
        <ClientWrapper>
          <Header />
          {children}
          <Footer data={footerData} />
        </ClientWrapper> 

      </body>
    </html>
  );
}
