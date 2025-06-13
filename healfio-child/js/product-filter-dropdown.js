 document.querySelectorAll('.filterDropdown .toggleBtn').forEach(button => {
    button.addEventListener('click', () => {
      const dropdown = button.nextElementSibling;
      const parent = button.closest('.filterDropdown');
      const isOpen = dropdown.style.display === 'block';

      // Close all dropdowns, remove active and selected classes
      document.querySelectorAll('.filterDropdown .dropdown').forEach(d => d.style.display = 'none');
      document.querySelectorAll('.filterDropdown .toggleBtn').forEach(b => b.classList.remove('active'));
      document.querySelectorAll('.filterDropdown').forEach(fd => fd.classList.remove('selected'));

      // If this one was closed, open it and add classes
      if (!isOpen) {
        dropdown.style.display = 'block';
        button.classList.add('active');
        parent.classList.add('selected');
      }
    });

    // Close all dropdowns on any click outside a filterDropdown
    document.addEventListener('click', (e) => {
      if (!e.target.closest('.filterDropdown')) {
        closeAllDropdowns();
      }
    });

  });


  const buttons = document.querySelectorAll('.filterDropdown .toggle-btn');

    buttons.forEach(button => {
      button.addEventListener('click', (e) => {
        e.stopPropagation(); // Prevent event from bubbling to document
        const dropdown = button.nextElementSibling;
        const parent = button.closest('.filterDropdown');
        const isOpen = dropdown.style.display === 'block';

        // Close all dropdowns
        closeAllDropdowns();

        // If the clicked one was closed, open it
        if (!isOpen) {
          dropdown.style.display = 'block';
          button.classList.add('active');
          parent.classList.add('selected');
        }
      });
    });

    // Close dropdowns on outside click
    document.addEventListener('click', (e) => {
      if (!e.target.closest('.filterDropdown')) {
        closeAllDropdowns();
      }
    });

    function closeAllDropdowns() {
      document.querySelectorAll('.filterDropdown .dropdown').forEach(d => d.style.display = 'none');
      document.querySelectorAll('.filterDropdown .toggle-btn').forEach(b => b.classList.remove('active'));
      document.querySelectorAll('.filterDropdown').forEach(fd => fd.classList.remove('selected'));
    }



  // THC / CBD Range slider
  const rangeInput = document.querySelectorAll(".range-input input"),
  priceInput = document.querySelectorAll(".price-input input"),
  range = document.querySelector(".slider .progress");
  const priceGap = 1; // Minimum difference allowed

  priceInput.forEach((input) => {
    input.setAttribute("min", "0.00");
    input.setAttribute("max", "45.00");
    input.setAttribute("step", "0.01");
  });

  rangeInput.forEach((input) => {
    input.setAttribute("min", "0.00");
    input.setAttribute("max", "45.00");
    input.setAttribute("step", "0.01");
  });

  priceInput.forEach((input) => {
    input.addEventListener("input", (e) => {
      let minPrice = parseFloat(priceInput[0].value),
          maxPrice = parseFloat(priceInput[1].value);

      if (maxPrice - minPrice >= priceGap && maxPrice <= parseFloat(rangeInput[1].max)) {
        if (e.target.classList.contains("input-min")) {
          rangeInput[0].value = minPrice;
          range.style.left = (minPrice / parseFloat(rangeInput[0].max)) * 100 + "%";
        } else {
          rangeInput[1].value = maxPrice;
          range.style.right = 100 - (maxPrice / parseFloat(rangeInput[1].max)) * 100 + "%";
        }
      }
    });
  });

  rangeInput.forEach((input) => {
    input.addEventListener("input", (e) => {
      let minVal = parseFloat(rangeInput[0].value),
          maxVal = parseFloat(rangeInput[1].value);

      if (maxVal - minVal < priceGap) {
        if (e.target.classList.contains("range-min")) {
          rangeInput[0].value = maxVal - priceGap;
        } else {
          rangeInput[1].value = minVal + priceGap;
        }
      } else {
        priceInput[0].value = minVal.toFixed(2);
        priceInput[1].value = maxVal.toFixed(2);
        range.style.left = (minVal / parseFloat(rangeInput[0].max)) * 100 + "%";
        range.style.right = 100 - (maxVal / parseFloat(rangeInput[1].max)) * 100 + "%";
      }
    });
  });

  const secondPriceInput = document.querySelectorAll(".price-input-second");
  const secondRangeInput = document.querySelectorAll(".range-input-second");
  const secondRange = document.querySelector(".range-second");
  const secondPriceGap = 0.001;

  secondPriceInput.forEach((input) => {
    input.addEventListener("input", (e) => {
      let minPrice = parseFloat(secondPriceInput[0].value),
          maxPrice = parseFloat(secondPriceInput[1].value);

      if (maxPrice - minPrice >= secondPriceGap && maxPrice <= parseFloat(secondRangeInput[1].max)) {
        if (e.target.classList.contains("input-min")) {
          secondRangeInput[0].value = minPrice;
          secondRange.style.left = (minPrice / parseFloat(secondRangeInput[0].max)) * 100 + "%";
        } else {
          secondRangeInput[1].value = maxPrice;
          secondRange.style.right = 100 - (maxPrice / parseFloat(secondRangeInput[1].max)) * 100 + "%";
        }
      }
    });
  });

  secondRangeInput.forEach((input) => {
    input.addEventListener("input", (e) => {
      let minVal = parseFloat(secondRangeInput[0].value),
          maxVal = parseFloat(secondRangeInput[1].value);

      if (maxVal - minVal < secondPriceGap) {
        if (e.target.classList.contains("range-min")) {
          secondRangeInput[0].value = maxVal - secondPriceGap;
        } else {
          secondRangeInput[1].value = minVal + secondPriceGap;
        }
      } else {
        secondPriceInput[0].value = minVal.toFixed(3);
        secondPriceInput[1].value = maxVal.toFixed(3);
        secondRange.style.left = (minVal / parseFloat(secondRangeInput[0].max)) * 100 + "%";
        secondRange.style.right = 100 - (maxVal / parseFloat(secondRangeInput[1].max)) * 100 + "%";
      }
    });
  });

