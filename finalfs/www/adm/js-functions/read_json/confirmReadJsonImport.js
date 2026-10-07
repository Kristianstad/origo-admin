function confirmReadJsonImport() {
    return window.confirm('Att importera en hel origokonfiguration i JSON-format till databasen är riskabelt. Det kan innebära att ett stort antal redundanta poster läggs till i databasen och att redan befintliga origokonfigurationer slutar att fungera. Är du säker på att du vill importera till databasen?');
}