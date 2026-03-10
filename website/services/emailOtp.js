/**
 * Mock Email OTP Service
 *
 * Replace these functions with real API calls when backend endpoints are ready.
 *
 * Expected real endpoints:
 *   POST /api/otp/send   { email } -> { success: true, message: "OTP sent" }
 *   POST /api/otp/verify  { email, otp } -> { success: true/false, message: "..." }
 */

const MOCK_DELAY = 1500;

export const sendEmailOtp = async (email) => {
  return new Promise((resolve) => {
    setTimeout(() => {
      console.log(`[Mock OTP] Sent OTP to ${email}`);
      resolve({ success: true, message: "OTP sent successfully" });
    }, MOCK_DELAY);
  });
};

export const verifyEmailOtp = async (email, otp) => {
  return new Promise((resolve) => {
    setTimeout(() => {
      const isValid = /^\d{6}$/.test(otp);
      console.log(`[Mock OTP] Verify for ${email}: ${isValid ? "valid" : "invalid"}`);
      resolve({
        success: isValid,
        message: isValid ? "Email verified successfully" : "Invalid OTP code",
      });
    }, MOCK_DELAY);
  });
};
