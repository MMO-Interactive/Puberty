const tourContent = {
    height: {
        title: "Height & shape can change quickly.",
        body: "Bodies can grow fast, hips may widen, and clothes may fit differently for a while.",
        tip: "Growth spurts can make you feel awkward for a bit. That is common."
    },
    skin: {
        title: "Skin may get oilier during puberty.",
        body: "Oil, sweat, and stronger body odor can increase during puberty.",
        tip: "Gentle skin care helps. Pimples do not mean you are dirty."
    },
    breasts: {
        title: "Breasts often develop in stages.",
        body: "Breasts often grow in stages and one side can develop faster than the other.",
        tip: "Tenderness can happen. Different timing is normal."
    },
    hair: {
        title: "New hair can appear in new places.",
        body: "Hair can grow under the arms and around the vulva, and its texture can vary.",
        tip: "Keeping it or grooming it is a personal choice."
    },
    periods: {
        title: "Periods and discharge are part of growing up.",
        body: "Discharge can begin before periods. Early cycles can be irregular.",
        tip: "Tracking dates helps you prepare and notice patterns."
    },
    feelings: {
        title: "Emotions can feel bigger for a while.",
        body: "Hormone changes can affect feelings, energy, and reactions.",
        tip: "Talking to a trusted adult can make changes easier to handle."
    },
    sleep: {
        title: "Sleep patterns can shift during puberty.",
        body: "You may feel sleepy earlier or later, and growth spurts can make you more tired than usual.",
        tip: "A steady bedtime routine can improve energy and mood."
    },
    nutrition: {
        title: "Fuel and hydration matter more while growing.",
        body: "Regular meals, water, and iron-rich foods can support focus, growth, and cycle health.",
        tip: "Keep a simple snack-and-water routine for school days."
    },
    boundaries: {
        title: "Personal boundaries are part of body safety.",
        body: "As you grow, it is important to know your body belongs to you and your comfort matters.",
        tip: "If something feels wrong, tell a trusted adult right away."
    }
};

const tourButtons = document.querySelectorAll(".tour-card");
const tourCopy = document.getElementById("tour-copy");

function renderTour(key) {
    const item = tourContent[key];
    if (!item || !tourCopy) {
        return;
    }

    tourCopy.innerHTML = `
        <h3>${item.title}</h3>
        <p>${item.body}</p>
        <p class="tour-tip">${item.tip}</p>
    `;
}

tourButtons.forEach((button) => {
    button.addEventListener("click", () => {
        tourButtons.forEach((card) => card.classList.remove("is-active"));
        button.classList.add("is-active");
        renderTour(button.dataset.tour);
    });
});
