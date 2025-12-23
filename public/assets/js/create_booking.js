document.getElementById("bookingForm").addEventListener("submit", async function (e) {
  e.preventDefault();

  const token = localStorage.getItem("token");
  if (!token) {
    alert("Please login first");
    window.location.href = "login.html";
    return;
  }

  const bookingData = {
    hotel_id: document.getElementById("hotel_id").value,
    room_id: document.getElementById("room_id").value,
    check_in: document.getElementById("check_in").value,
    check_out: document.getElementById("check_out").value,
    guest_count: document.getElementById("guest_count").value,
    special_requests: document.getElementById("special_requests").value
  };

  // Simple validation
  if (bookingData.check_out <= bookingData.check_in) {
    alert("Check-out must be after check-in");
    return;
  }

  try {
    const response = await fetch(
      "/hotelmanagement/api/booking/create_booking.php",
      {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "Authorization": "Bearer " + token
        },
        body: JSON.stringify(bookingData)
      }
    );

    const result = await response.json();
    const messageEl = document.getElementById("message");

    if (result.success) {
      messageEl.style.color = "green";
      messageEl.innerText =
        "Booking successful! Total price: " + result.total_price;
      document.getElementById("bookingForm").reset();
    } else {
      messageEl.style.color = "red";
      messageEl.innerText = result.message;
    }

  } catch (error) {
    console.error(error);
    alert("Server error. Try again later.");
  }
});
