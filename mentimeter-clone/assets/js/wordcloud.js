function renderWordCloud(containerId, responses) {
    const container = document.getElementById(containerId);
    if (!container || !responses) return;

    // Process responses into word frequency
    const wordCounts = {};
    responses.forEach(response => {
        const words = response.response_text.trim().toLowerCase().split(/\s+/);
        words.forEach(word => {
            if (word) {
                wordCounts[word] = (wordCounts[word] || 0) + 1;
            }
        });
    });

    // Convert to WordCloud2 format: [[word, frequency], ...]
    const wordList = Object.entries(wordCounts).map(([word, count]) => [word, count]);

    // Clear container
    container.innerHTML = '';

    // Render Word Cloud
    WordCloud(container, {
        list: wordList,
        gridSize: 8,
        weightFactor: 20,
        fontFamily: 'Segoe UI, Tahoma, sans-serif',
        color: 'random-dark',
        backgroundColor: '#fff',
        rotateRatio: 0.5,
        rotationSteps: 2,
        drawOutOfBound: false,
        shrinkToFit: true,
        minSize: 10
    });
}