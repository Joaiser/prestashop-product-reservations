export async function sendReservation(url, token, products) {
    const response = await fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': token
        },
        body: JSON.stringify({ ajax: 1, products: products })
    });
    return await response.json();
}